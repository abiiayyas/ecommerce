<?php

namespace App\Data\Shipping;

use App\Enums\ShippingProviderDriver;

final readonly class ShipmentData
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        public ShippingProviderDriver $provider,
        public string $externalId,
        public ?string $trackingNumber,
        public string $status,
        public ?string $courierCode = null,
        public ?string $courierService = null,
        public array $raw = [],
    ) {}
}
