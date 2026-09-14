<?php

namespace App\Actions\Api\V1\Callback;

use App\Actions\Ecommerce\Payment\ReconcilePaymentStatusAction;
use App\Enums\PaymentGatewayDriver;
use App\Models\Payment\Payment;
use App\Services\MidtransService;
use App\Services\Payments\PaymentGatewayManager;
use InvalidArgumentException;

class HandleMidtransCallbackAction
{
    public function __construct(
        public readonly MidtransService $midtransService,
        private readonly ReconcilePaymentStatusAction $reconcilePaymentStatus,
        private readonly PaymentGatewayManager $paymentGatewayManager,
    ) {}

    /** @param array<string, mixed> $payload */
    public function handle(array $payload): Payment
    {
        $this->validatePayload($payload);

        $orderId = (string) $payload['order_id'];
        $statusCode = (string) $payload['status_code'];
        $grossAmount = (string) $payload['gross_amount'];
        $signatureKey = (string) $payload['signature_key'];
        $transactionStatus = (string) $payload['transaction_status'];

        if (! $this->midtransService->validateSignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
            throw new \Exception('Invalid signature key', 403);
        }

        $this->validateTransactionStatus($transactionStatus);

        $payment = Payment::query()
            ->where('driver', PaymentGatewayDriver::Midtrans->value)
            ->where('order_id', $orderId)
            ->first();

        if ($payment === null) {
            throw new \Exception('Transaction not found', 404);
        }

        $transaction = $this->paymentGatewayManager
            ->driver(PaymentGatewayDriver::Midtrans)
            ->paymentStatus($orderId);

        return $this->reconcilePaymentStatus->handle($payment, $transaction);
    }

    private function validateTransactionStatus(string $transactionStatus): void
    {
        if (! in_array(strtolower($transactionStatus), [
            'pending',
            'capture',
            'settlement',
            'deny',
            'failure',
            'cancel',
            'expire',
        ], true)) {
            throw new InvalidArgumentException('Unsupported Midtrans transaction status.', 400);
        }
    }

    /** @param array<string, mixed> $payload */
    private function validatePayload(array $payload): void
    {
        foreach (['order_id', 'status_code', 'gross_amount', 'signature_key', 'transaction_status'] as $field) {
            if (! isset($payload[$field]) || ! is_scalar($payload[$field])) {
                throw new InvalidArgumentException("Missing Midtrans callback field: {$field}", 400);
            }
        }
    }
}
