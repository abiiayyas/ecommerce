<?php

namespace App\Data\Shipping;

use App\Enums\ShippingProviderDriver;

final readonly class ShippingAreaData
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        public ShippingProviderDriver $provider,
        public string $id,
        public string $name,
        public ?string $province = null,
        public ?string $city = null,
        public ?string $postalCode = null,
        public array $raw = [],
    ) {}
}
