<?php

namespace App\Actions\Cms\Supplier\Offer;

use App\Models\Product\ProductFlat;
use App\Models\Supplier\Supplier;
use App\Models\Supplier\SupplierOffer;
use App\Models\Supplier\SupplierWarehouse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class StoreSupplierOfferAction
{
    public function handle(
        Supplier $supplier,
        SupplierWarehouse $warehouse,
        ProductFlat $productFlat,
        array $data,
    ): SupplierOffer {
        Gate::authorize('create', SupplierOffer::class);

        $this->ensureWarehouseBelongsToSupplier($supplier, $warehouse);

        return SupplierOffer::query()->create([
            ...Arr::only($data, [
                'supplier_sku',
                'cost_price',
                'available_stock',
                'is_available',
                'is_active',
                'notes',
            ]),
            'supplier_id' => $supplier->getKey(),
            'supplier_warehouse_id' => $warehouse->getKey(),
            'product_flat_id' => $productFlat->getKey(),
        ]);
    }

    private function ensureWarehouseBelongsToSupplier(Supplier $supplier, SupplierWarehouse $warehouse): void
    {
        if (! $warehouse->supplier()->whereKey($supplier->getKey())->exists()) {
            throw ValidationException::withMessages([
                'supplier_warehouse_id' => 'Gudang harus dimiliki oleh supplier yang dipilih.',
            ]);
        }
    }
}
