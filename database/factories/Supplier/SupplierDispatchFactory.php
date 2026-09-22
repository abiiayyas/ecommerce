<?php

namespace Database\Factories\Supplier;

use App\Enums\SupplierDispatchStatus;
use App\Models\Order\OrderShop;
use App\Models\Supplier\SupplierDispatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierDispatch>
 */
class SupplierDispatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_shop_id' => OrderShop::factory(),
            'supplier_id' => 1,
            'supplier_warehouse_id' => 1,
            'status' => SupplierDispatchStatus::Pending,
            'order_snapshot' => [],
        ];
    }
}
