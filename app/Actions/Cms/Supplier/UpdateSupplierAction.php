<?php

namespace App\Actions\Cms\Supplier;

use App\Models\Supplier\Supplier;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class UpdateSupplierAction
{
    public function handle(Supplier $supplier, array $data): Supplier
    {
        Gate::authorize('update', $supplier);

        $supplier->update(Arr::only($data, [
            'code',
            'name',
            'contact_name',
            'contact_email',
            'contact_phone',
            'order_phone',
            'notes',
            'is_active',
        ]));

        return $supplier->refresh();
    }
}
