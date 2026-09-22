<?php

namespace Database\Factories\Supplier;

use App\Models\Product\ProductFlat;
use App\Models\Supplier\Supplier;
use App\Models\Supplier\SupplierOffer;
use App\Models\Supplier\SupplierWarehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierOffer>
 */
class SupplierOfferFactory extends Factory
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
            'supplier_warehouse_id' => fn (array $attributes) => SupplierWarehouse::factory()->create([
                'supplier_id' => $attributes['supplier_id'],
            ])->getKey(),
            'product_flat_id' => ProductFlat::factory(),
            'supplier_sku' => fake()->unique()->bothify('SKU-####-????'),
            'cost_price' => fake()->numberBetween(5_000, 500_000),
            'available_stock' => fake()->numberBetween(0, 1_000),
            'is_available' => true,
            'is_active' => true,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function unavailable(): static
    {
        return $this->state(fn (): array => ['is_available' => false]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
