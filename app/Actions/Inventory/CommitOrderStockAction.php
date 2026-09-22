<?php

namespace App\Actions\Inventory;

use App\Enums\StockReservationStatus;
use App\Models\Order\Order;

class CommitOrderStockAction
{
    public function handle(Order $order): void
    {
        $order->stockReservations()
            ->where('status', StockReservationStatus::Active)
            ->update([
                'status' => StockReservationStatus::Committed,
                'committed_at' => now(),
                'expires_at' => null,
                'updated_at' => now(),
            ]);
    }
}
