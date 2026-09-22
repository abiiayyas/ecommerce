<?php

namespace App\Data\Shipping;

use InvalidArgumentException;

final readonly class ShippingRateRequestData
{
    /**
     * @param  list<ShippingItemData>  $items
     * @param  list<string>  $courierCodes
     */
    public function __construct(
        public string $originAreaId,
        public string $destinationAreaId,
        public array $items,
        public array $courierCodes = [],
        public ?int $codAmount = null,
    ) {
        if (blank($this->originAreaId) || blank($this->destinationAreaId)) {
            throw new InvalidArgumentException('Origin and destination area IDs are required.');
        }

        if ($this->items === []) {
            throw new InvalidArgumentException('At least one shipping item is required.');
        }

        if ($this->codAmount !== null && $this->codAmount < 0) {
            throw new InvalidArgumentException('COD amount must not be negative.');
        }
    }
}
