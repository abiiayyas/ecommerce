<?php

namespace App\Actions\Ecommerce\Payment;

use App\Enums\PaymentGatewayDriver;
use App\Models\Payment\Payment;
use App\Services\Payments\PaymentGatewayManager;
use InvalidArgumentException;

final class RefreshPaymentStatusAction
{
    public function __construct(
        private readonly PaymentGatewayManager $paymentGatewayManager,
        private readonly ReconcilePaymentStatusAction $reconcilePaymentStatus,
    ) {}

    public function handle(Payment $payment): Payment
    {
        $storedPayment = Payment::query()->findOrFail($payment->getKey());
        $driver = PaymentGatewayDriver::tryFrom((string) $storedPayment->driver);
        $orderId = (string) $storedPayment->order_id;

        if ($driver === null) {
            throw new InvalidArgumentException("Unsupported payment gateway [{$storedPayment->driver}].");
        }

        if (blank($orderId)) {
            throw new InvalidArgumentException('Payment order ID is required.');
        }

        $transaction = $this->paymentGatewayManager
            ->driver($driver)
            ->paymentStatus($orderId);

        return $this->reconcilePaymentStatus->handle($storedPayment, $transaction);
    }
}
