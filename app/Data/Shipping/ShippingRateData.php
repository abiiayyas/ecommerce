<?php

namespace App\Data\Shipping;

use App\Enums\ShippingProviderDriver;

final readonly class ShippingRateData
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        public ShippingProviderDriver $provider,
        public string $courierCode,
        public string $courierName,
        public string $serviceCode,
        public string $serviceName,
        public int $price,
        public ?string $duration = null,
        public bool $supportsCod = false,
        public array $raw = [],
    ) {}
}
