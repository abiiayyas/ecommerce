<?php

namespace App\Services\Payments;

use RuntimeException;

final class PaymentTransactionNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Payment transaction was not found.', 404);
    }
}
