<?php

namespace App\Actions\Inventory;

use App\Enums\FulfillmentType;
use App\Enums\StockReservationStatus;
use App\Models\Inventory\StockReservation;
use App\Models\Order\Order;
use App\Models\Product\ProductFlat;
use Illuminate\Validation\ValidationException;

class ReserveOrderStockAction
{
    public function handle(Order $order, ProductFlat $productFlat, int $quantity, bool $commitImmediately = false): StockReservation
    {
        $lockedFlat = ProductFlat::query()
            ->with('activeSupplierOffer')
            ->lockForUpdate()
            ->findOrFail($productFlat->getKey());

        $supplierOfferId = null;

        if ($lockedFlat->fulfillment_type === FulfillmentType::SupplierDropship) {
            $offer = $lockedFlat->activeSupplierOffer;

            if ($offer === null || ! $offer->is_active || ! $offer->is_available) {
                throw ValidationException::withMessages([
                    'product_flat_id' => 'Sumber supplier untuk produk ini sedang tidak tersedia.',
                ]);
            }

            if ($offer->available_stock !== null && $offer->available_stock < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stok supplier tidak mencukupi.',
                ]);
            }

            if ($offer->available_stock !== null) {
                $offer->decrement('available_stock', $quantity);
            }

            $supplierOfferId = $offer->getKey();
        } elseif (! $lockedFlat->is_unlimited_stock) {
            if ($lockedFlat->stock < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stok produk tidak mencukupi.',
                ]);
            }

            $lockedFlat->decrement('stock', $quantity);
        }

        return StockReservation::query()->create([
            'order_id' => $order->getKey(),
            'product_flat_id' => $lockedFlat->getKey(),
            'supplier_offer_id' => $supplierOfferId,
            'quantity' => $quantity,
            'status' => $commitImmediately ? StockReservationStatus::Committed : StockReservationStatus::Active,
            'expires_at' => $commitImmediately ? null : now()->addDay(),
            'committed_at' => $commitImmediately ? now() : null,
        ]);
    }
}
