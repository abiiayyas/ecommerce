<?php

namespace App\Data\Shipping;

use InvalidArgumentException;

final readonly class CreateShipmentData
{
    /**
     * @param  array<string, mixed>  $origin
     * @param  array<string, mixed>  $destination
     * @param  list<ShippingItemData>  $items
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $reference,
        public array $origin,
        public array $destination,
        public array $items,
        public string $courierCode,
        public string $courierService,
        public ?int $codAmount = null,
        public array $metadata = [],
    ) {
        if (blank($this->reference) || blank($this->courierCode) || blank($this->courierService)) {
            throw new InvalidArgumentException('Shipment reference and courier details are required.');
        }

        if ($this->origin === [] || $this->destination === [] || $this->items === []) {
            throw new InvalidArgumentException('Shipment origin, destination, and items are required.');
        }
    }
}
