<?php

namespace App\Actions\Cms\Supplier\Warehouse;

use App\Models\Supplier\SupplierWarehouse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class UpdateSupplierWarehouseAction
{
    public function handle(SupplierWarehouse $warehouse, array $data): SupplierWarehouse
    {
        Gate::authorize('update', $warehouse);

        $warehouse->update(Arr::only($data, [
            'name',
            'contact_name',
            'contact_phone',
            'address',
            'postal_code',
            'area_name',
            'latitude',
            'longitude',
            'primary_shipping_provider',
            'fallback_shipping_provider',
            'provider_area_ids',
            'provider_address_ids',
            'is_active',
        ]));

        return $warehouse->refresh();
    }
}
