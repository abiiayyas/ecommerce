<?php

namespace App\Actions\Inventory;

use App\Enums\FulfillmentType;
use App\Enums\StockReservationStatus;
use App\Models\Inventory\StockReservation;
use Illuminate\Support\Facades\DB;

class ReleaseExpiredReservationsAction
{
    public function handle(): int
    {
        $released = 0;

        StockReservation::query()
            ->where('status', StockReservationStatus::Active)
            ->where('expires_at', '<=', now())
            ->lazyById()
            ->each(function (StockReservation $reservation) use (&$released): void {
                DB::transaction(function () use ($reservation, &$released): void {
                    $locked = StockReservation::query()
                        ->with('productFlat')
                        ->lockForUpdate()
                        ->findOrFail($reservation->getKey());

                    if ($locked->status !== StockReservationStatus::Active || $locked->expires_at?->isFuture()) {
                        return;
                    }

                    if ($locked->supplier_offer_id !== null) {
                        $offer = $locked->productFlat->supplierOffers()
                            ->whereKey($locked->supplier_offer_id)
                            ->lockForUpdate()
                            ->first();

                        if ($offer?->available_stock !== null) {
                            $offer->increment('available_stock', $locked->quantity);
                        }
                    } elseif (! $locked->productFlat->is_unlimited_stock
                        && $locked->productFlat->fulfillment_type === FulfillmentType::OwnedStock) {
                        $locked->productFlat()->lockForUpdate()->firstOrFail()->increment('stock', $locked->quantity);
                    }

                    $locked->update([
                        'status' => StockReservationStatus::Released,
                        'released_at' => now(),
                    ]);
                    $released++;
                }, attempts: 3);
            });

        return $released;
    }
}
