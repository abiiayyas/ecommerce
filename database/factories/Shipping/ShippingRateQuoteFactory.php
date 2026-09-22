<?php

namespace Database\Factories\Shipping;

use App\Models\Product\ProductFlat;
use App\Models\Shipping\ShippingRateQuote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingRateQuote>
 */
class ShippingRateQuoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_flat_id' => ProductFlat::factory(),
            'provider' => 'biteship',
            'courier_company' => 'jne',
            'courier_service' => 'reg',
            'description' => 'Regular',
            'price' => 15_000,
            'destination_area_id' => fake()->uuid(),
            'quantity' => 1,
            'payload_hash' => hash('sha256', 'fixture'),
            'provider_payload' => [],
            'expires_at' => now()->addMinutes(15),
        ];
    }
}
