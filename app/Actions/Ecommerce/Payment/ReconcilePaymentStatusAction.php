<?php

namespace App\Actions\Ecommerce\Payment;

use App\Actions\Inventory\CommitOrderStockAction;
use App\Contracts\Marketing\MarketingEventPublisher;
use App\Data\Marketing\MarketingEventData;
use App\Data\Payments\PaymentTransactionData;
use App\Enums\OrderFulfillmentStatus;
use App\Enums\PaymentGatewayDriver;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SalesChannel;
use App\Mail\OrderPaid;
use App\Mail\OrderPaymentFailed;
use App\Models\Order\Order;
use App\Models\Payment\Payment;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Spatie\Activitylog\Models\Activity;
use UnexpectedValueException;

final class ReconcilePaymentStatusAction
{
    private const string PROVIDER_STATUS_PREFIX = 'provider-status:';

    private const string NOTIFICATION_LOG_NAME = 'payment';

    private const string NOTIFICATION_LOG_EVENT = 'notification_sent';

    private const string PAID_NOTIFICATION_DESCRIPTION = 'Order paid email sent';

    private const string FAILED_NOTIFICATION_DESCRIPTION = 'Order payment failed email sent';

    public function handle(
        Payment $payment,
        PaymentTransactionData $transaction,
        string $mismatchMessage = 'Payment gateway returned a mismatched payment status response.',
    ): Payment {
        return DB::transaction(function () use ($payment, $transaction, $mismatchMessage): Payment {
            $lockedPayment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->getKey());
            $lockedOrder = $this->lockedOrder($lockedPayment);

            $this->validateTransaction($lockedPayment, $lockedOrder, $transaction, $mismatchMessage);

            if ($lockedPayment->paid_at !== null) {
                $this->retryNotificationAfterCommit($lockedPayment, $lockedOrder);

                return $lockedPayment;
            }

            $providerTerminalStatus = $this->providerTerminalStatus($lockedPayment);

            if ($providerTerminalStatus !== null && $transaction->status !== PaymentStatus::Paid) {
                $this->retryNotificationAfterCommit($lockedPayment, $lockedOrder);

                return $lockedPayment;
            }

            $attributes = $this->transactionAttributes($lockedPayment, $transaction);

            if ($transaction->status === PaymentStatus::Paid) {
                $attributes['paid_at'] = now();

                if ($providerTerminalStatus !== null) {
                    $attributes['account_code'] = null;
                }
            } elseif ($this->isProviderTerminalStatus($transaction->status)) {
                $attributes['account_code'] = self::PROVIDER_STATUS_PREFIX.$transaction->status->value;
                $attributes['expired_at'] = now();
            }

            $lockedPayment->update($attributes);

            if ($lockedOrder === null) {
                return $lockedPayment->refresh();
            }

            $fee = $transaction->totalPayment - (int) round((float) $lockedPayment->amount);
            $lockedOrder->update(['payment_fee' => $fee]);

            if ($transaction->status === PaymentStatus::Paid) {
                $lockedOrder->update(['status' => true]);
                if ($lockedOrder->sales_channel === SalesChannel::AdLanding) {
                    app(CommitOrderStockAction::class)->handle($lockedOrder);
                    $lockedOrder->update(['fulfillment_status' => OrderFulfillmentStatus::AwaitingSupplier]);
                    $this->sendLandingPaidSignalsAfterCommit($lockedOrder);
                }
                $this->sendAfterCommit($lockedPayment, $lockedOrder, PaymentStatus::Paid);
            } elseif ($this->isProviderTerminalStatus($transaction->status)) {
                $this->sendAfterCommit($lockedPayment, $lockedOrder, $transaction->status);
            }

            return $lockedPayment->refresh();
        }, attempts: 3);
    }

    public function retryNotificationAfterCommit(Payment $payment, ?Order $order = null): void
    {
        $status = $payment->paid_at !== null
            ? PaymentStatus::Paid
            : $this->providerTerminalStatus($payment);

        if ($status === null) {
            return;
        }

        $order ??= $this->lockedOrder($payment);

        if ($order === null) {
            return;
        }

        $this->sendAfterCommit($payment, $order, $status);
    }

    private function providerTerminalStatus(Payment $payment): ?PaymentStatus
    {
        $accountCode = $payment->account_code;

        if (! is_string($accountCode) || ! str_starts_with($accountCode, self::PROVIDER_STATUS_PREFIX)) {
            return null;
        }

        $status = PaymentStatus::tryFrom(substr($accountCode, strlen(self::PROVIDER_STATUS_PREFIX)));

        return $status !== null && $this->isProviderTerminalStatus($status) ? $status : null;
    }

    private function isProviderTerminalStatus(PaymentStatus $status): bool
    {
        return in_array($status, [
            PaymentStatus::Failed,
            PaymentStatus::Cancelled,
            PaymentStatus::Expired,
        ], true);
    }

    private function validateTransaction(
        Payment $payment,
        ?Order $order,
        PaymentTransactionData $transaction,
        string $message,
    ): void {
        $principal = (int) round((float) $payment->amount);
        $storedTotal = (int) round((float) $payment->total);
        $storedMethod = $this->storedPaymentMethod($payment);
        $expectedProviderAmount = $payment->driver === PaymentGatewayDriver::Midtrans->value
            ? $storedTotal
            : $principal;

        $hasTransactionConflict = filled($payment->transaction_id)
            && ! hash_equals((string) $payment->transaction_id, $transaction->id);
        $hasTotalConflict = $payment->driver === PaymentGatewayDriver::Midtrans->value
            ? $transaction->totalPayment !== $storedTotal
            : filled($payment->transaction_id)
                && $payment->driver === PaymentGatewayDriver::Paywuz->value
                && $transaction->totalPayment !== $storedTotal;
        $transactionBelongsToAnotherPayment = Payment::query()
            ->where('transaction_id', $transaction->id)
            ->whereKeyNot($payment->getKey())
            ->lockForUpdate()
            ->exists();
        $orderPrincipalMismatch = $order !== null
            && (int) round((float) $order->total) !== $principal;

        if (
            ! hash_equals((string) $payment->order_id, $transaction->orderId)
            || $hasTransactionConflict
            || $hasTotalConflict
            || $transactionBelongsToAnotherPayment
            || $orderPrincipalMismatch
            || $transaction->amount !== $expectedProviderAmount
            || $transaction->totalPayment < $principal
            || ! $this->paymentMethodIsCompatible($payment, $storedMethod, $transaction)
        ) {
            throw new UnexpectedValueException($message);
        }
    }

    private function storedPaymentMethod(Payment $payment): ?PaymentMethod
    {
        $channel = trim((string) $payment->channel);
        $paymentMethod = PaymentMethod::tryFrom(strtolower($channel))
            ?? PaymentMethod::fromProviderCode($channel);

        if ($paymentMethod !== null) {
            return $paymentMethod;
        }

        if (
            $payment->driver === PaymentGatewayDriver::Paywuz->value
            && in_array(strtoupper($channel), ['MANDIRIVA', 'PERMATAVA'], true)
        ) {
            return PaymentMethod::Va;
        }

        return null;
    }

    private function paymentMethodIsCompatible(
        Payment $payment,
        ?PaymentMethod $storedMethod,
        PaymentTransactionData $transaction,
    ): bool {
        if ($storedMethod === null) {
            return false;
        }

        $storedProviderCode = strtoupper(trim((string) $payment->channel));
        $isMetaVa = $storedProviderCode === 'VA';
        $methodMatches = $storedMethod === $transaction->paymentMethod
            || ($isMetaVa && $transaction->paymentMethod !== PaymentMethod::Qris);

        if (! $methodMatches) {
            return false;
        }

        $legacyChannels = array_map(
            static fn (PaymentMethod $method): string => $method->value,
            PaymentMethod::cases(),
        );

        if (in_array((string) $payment->channel, $legacyChannels, true) || $isMetaVa) {
            return true;
        }

        return $transaction->providerCode !== null
            && hash_equals($storedProviderCode, strtoupper($transaction->providerCode));
    }

    private function transactionAttributes(Payment $payment, PaymentTransactionData $transaction): array
    {
        $principal = (int) round((float) $payment->amount);
        $destination = $transaction->paymentNumber ?? $transaction->paymentUrl;

        return [
            'transaction_id' => $transaction->id,
            'account_number' => $destination ?? $payment->account_number,
            'expired_at' => $transaction->expiresAt ?? $payment->expired_at,
            'fee' => $transaction->totalPayment - $principal,
            'total' => $transaction->totalPayment,
        ];
    }

    private function lockedOrder(Payment $payment): ?Order
    {
        if ($payment->payable_type !== Order::class) {
            return null;
        }

        return Order::query()
            ->with('user')
            ->lockForUpdate()
            ->find($payment->payable_id);
    }

    private function sendAfterCommit(Payment $payment, Order $order, PaymentStatus $status): void
    {
        $paymentId = $payment->getKey();
        $orderId = $order->getKey();

        DB::afterCommit(function () use ($paymentId, $orderId, $status): void {
            $notificationType = $this->notificationType($status);

            Cache::store('database')
                ->lock('payment-notification:'.$paymentId.':'.$notificationType, 60)
                ->block(30, function () use ($paymentId, $orderId, $status, $notificationType): void {
                    $storedPayment = Payment::query()->find($paymentId);

                    if ($storedPayment === null || $this->notificationWasSent($storedPayment, $notificationType)) {
                        return;
                    }

                    $storedOrder = Order::query()->with('user')->find($orderId);

                    if ($storedOrder === null) {
                        return;
                    }

                    $email = $storedOrder->user?->email ?? data_get($storedOrder->guest_data, 'contact_email');

                    if (blank($email)) {
                        return;
                    }

                    $mail = $status === PaymentStatus::Paid
                        ? new OrderPaid($storedOrder)
                        : new OrderPaymentFailed($storedOrder);

                    Mail::to($email)->send($mail);

                    Activity::query()->create([
                        'log_name' => self::NOTIFICATION_LOG_NAME,
                        'description' => $this->notificationDescription($notificationType),
                        'subject_type' => $storedPayment->getMorphClass(),
                        'subject_id' => $storedPayment->getKey(),
                        'event' => self::NOTIFICATION_LOG_EVENT,
                        'properties' => [
                            'notification' => $notificationType,
                        ],
                    ]);
                });
        });
    }

    private function notificationType(PaymentStatus $status): string
    {
        return $status === PaymentStatus::Paid ? 'paid' : 'failed';
    }

    private function notificationDescription(string $notificationType): string
    {
        return $notificationType === 'paid'
            ? self::PAID_NOTIFICATION_DESCRIPTION
            : self::FAILED_NOTIFICATION_DESCRIPTION;
    }

    private function notificationWasSent(Payment $payment, string $notificationType): bool
    {
        return Activity::query()
            ->where('log_name', self::NOTIFICATION_LOG_NAME)
            ->where('event', self::NOTIFICATION_LOG_EVENT)
            ->where('description', $this->notificationDescription($notificationType))
            ->where('subject_type', $payment->getMorphClass())
            ->where('subject_id', $payment->getKey())
            ->exists();
    }

    private function sendLandingPaidSignalsAfterCommit(Order $order): void
    {
        DB::afterCommit(function () use ($order): void {
            $guest = $order->guest_data ?? [];
            $sourceUrl = $order->landingPage?->slug
                ? route('landing.show', ['slug' => $order->landingPage->slug])
                : route('home');
            app(MarketingEventPublisher::class)->publish(new MarketingEventData(
                eventId: app(MarketingEventPublisher::class)->newEventId(),
                name: 'Purchase',
                occurredAt: now(),
                sourceUrl: $sourceUrl,
                customerData: [
                    'email' => $guest['contact_email'] ?? null,
                    'phone' => $guest['contact_phone'] ?? null,
                    'external_id' => (string) $order->getKey(),
                ],
                customData: [
                    'currency' => 'IDR',
                    'value' => (float) $order->total,
                    'order_id' => $order->reference,
                ],
            ));

            if (filled($guest['contact_phone'] ?? null)) {
                app(NotificationDispatcher::class)->sendWhatsApp(
                    messageType: 'payment_success',
                    recipient: (string) $guest['contact_phone'],
                    message: "Pembayaran pesanan {$order->reference} berhasil. Pesanan segera diproses.",
                    idempotencyKey: "order:{$order->getKey()}:paid",
                    context: ['order_id' => $order->getKey()],
                );
            }
        });
    }
}
