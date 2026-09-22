<?php

namespace App\Actions\Cms\Supplier\Offer;

use App\Models\Product\ProductFlat;
use App\Models\Supplier\SupplierOffer;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DeleteSupplierOfferAction
{
    public function handle(SupplierOffer $offer): bool
    {
        Gate::authorize('delete', $offer);

        if (ProductFlat::query()->where('active_supplier_offer_id', $offer->getKey())->exists()) {
            throw ValidationException::withMessages([
                'supplier_offer' => 'Penawaran aktif harus dilepas dari produk sebelum dihapus.',
            ]);
        }

        return (bool) $offer->delete();
    }
}
