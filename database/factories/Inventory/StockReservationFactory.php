<?php

namespace Database\Factories\Inventory;

use App\Enums\StockReservationStatus;
use App\Models\Inventory\StockReservation;
use App\Models\Order\Order;
use App\Models\Product\ProductFlat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockReservation>
 */
class StockReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_flat_id' => ProductFlat::factory(),
            'quantity' => 1,
            'status' => StockReservationStatus::Active,
            'expires_at' => now()->addMinutes(30),
        ];
    }
}
