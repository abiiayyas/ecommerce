<?php

namespace App\Data\Payments;

use App\Enums\PaymentMethod;

final readonly class PaymentMethodData
{
    /**
     * @param  list<array{minimumAmount: ?int, maximumAmount: ?int, flatFee: int, percentageFeeBasisPoints: int}>  $feeTiers
     */
    public function __construct(
        public PaymentMethod $paymentMethod,
        public string $providerCode,
        public string $name,
        public string $type,
        public int $flatFee = 0,
        public int $percentageFeeBasisPoints = 0,
        public ?int $totalFee = null,
        public ?int $minimumAmount = null,
        public ?int $maximumAmount = null,
        public bool $feeByMerchant = false,
        public array $feeTiers = [],
    ) {}
}
