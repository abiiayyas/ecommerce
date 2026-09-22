<?php

namespace App\Actions\Cms\Product\Product;

use App\Enums\FulfillmentType;
use App\Models\Product\Product;
use App\Models\Product\ProductFlat;
use App\Models\Supplier\SupplierOffer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SetProductFulfillmentAction
{
    public function handle(
        ProductFlat $productFlat,
        FulfillmentType $fulfillmentType,
        ?SupplierOffer $supplierOffer = null,
    ): ProductFlat {
        Gate::authorize('update'.Product::class);

        return DB::transaction(function () use ($productFlat, $fulfillmentType, $supplierOffer): ProductFlat {
            $lockedProductFlat = ProductFlat::query()->lockForUpdate()->findOrFail($productFlat->getKey());

            if ($fulfillmentType === FulfillmentType::OwnedStock) {
                $lockedProductFlat->update([
                    'fulfillment_type' => FulfillmentType::OwnedStock,
                    'active_supplier_offer_id' => null,
                ]);

                return $lockedProductFlat->refresh();
            }

            $eligibleOffer = $this->eligibleOffer($lockedProductFlat, $supplierOffer);

            $lockedProductFlat->update([
                'fulfillment_type' => FulfillmentType::SupplierDropship,
                'active_supplier_offer_id' => $eligibleOffer->getKey(),
            ]);

            return $lockedProductFlat->refresh();
        });
    }

    private function eligibleOffer(ProductFlat $productFlat, ?SupplierOffer $supplierOffer): SupplierOffer
    {
        if (! $supplierOffer) {
            throw ValidationException::withMessages([
                'supplier_offer_id' => 'Penawaran supplier wajib dipilih untuk fulfillment dropship.',
            ]);
        }

        $eligibleOffer = SupplierOffer::query()
            ->with(['supplier', 'warehouse'])
            ->lockForUpdate()
            ->findOrFail($supplierOffer->getKey());

        if (
            $eligibleOffer->product_flat_id !== $productFlat->getKey()
            || ! $eligibleOffer->is_active
            || ! $eligibleOffer->is_available
            || ! $eligibleOffer->supplier->is_active
            || ! $eligibleOffer->warehouse->is_active
        ) {
            throw ValidationException::withMessages([
                'supplier_offer_id' => 'Penawaran supplier tidak aktif, tidak tersedia, atau bukan milik varian ini.',
            ]);
        }

        return $eligibleOffer;
    }
}
