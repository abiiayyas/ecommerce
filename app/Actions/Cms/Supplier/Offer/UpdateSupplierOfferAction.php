<?php

namespace App\Actions\Cms\Supplier\Offer;

use App\Models\Supplier\SupplierOffer;
use App\Models\Supplier\SupplierWarehouse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UpdateSupplierOfferAction
{
    public function handle(
        SupplierOffer $offer,
        SupplierWarehouse $warehouse,
        array $data,
    ): SupplierOffer {
        Gate::authorize('update', $offer);

        if ($warehouse->supplier_id !== $offer->supplier_id) {
            throw ValidationException::withMessages([
                'supplier_warehouse_id' => 'Gudang harus dimiliki oleh supplier penawaran.',
            ]);
        }

        $offer->update([
            ...Arr::only($data, [
                'supplier_sku',
                'cost_price',
                'available_stock',
                'is_available',
                'is_active',
                'notes',
            ]),
            'supplier_warehouse_id' => $warehouse->getKey(),
        ]);

        return $offer->refresh();
    }
}
