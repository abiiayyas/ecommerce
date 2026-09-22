<?php

namespace Database\Factories\Supplier;

use App\Models\Supplier\Supplier;
use App\Models\Supplier\SupplierWarehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierWarehouse>
 */
class SupplierWarehouseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'name' => fake()->unique()->city().' Warehouse',
            'contact_name' => fake()->name(),
            'contact_phone' => fake()->numerify('08##########'),
            'address' => fake()->address(),
            'postal_code' => fake()->postcode(),
            'area_name' => fake()->city(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'primary_shipping_provider' => 'biteship',
            'fallback_shipping_provider' => null,
            'provider_area_ids' => ['biteship' => fake()->bothify('ID???####')],
            'provider_address_ids' => [],
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
