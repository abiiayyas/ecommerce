<?php

namespace App\Contracts\Payments;

use App\Data\Payments\CreatePaymentData;
use App\Data\Payments\PaymentMethodData;
use App\Data\Payments\PaymentTransactionData;

interface PaymentGateway
{
    /** @return list<PaymentMethodData> */
    public function paymentMethods(): array;

    public function createPayment(CreatePaymentData $data): PaymentTransactionData;

    public function paymentStatus(string $orderId): PaymentTransactionData;
}
