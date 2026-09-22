<?php

namespace App\Data\Shipping;

use InvalidArgumentException;

final readonly class ShippingItemData
{
    public function __construct(
        public string $name,
        public int $value,
        public int $weightGrams,
        public int $quantity = 1,
        public ?string $description = null,
    ) {
        if (blank($this->name)) {
            throw new InvalidArgumentException('Shipping item name is required.');
        }

        if ($this->value < 0 || $this->weightGrams <= 0 || $this->quantity <= 0) {
            throw new InvalidArgumentException('Shipping item value, weight, or quantity is invalid.');
        }
    }
}
