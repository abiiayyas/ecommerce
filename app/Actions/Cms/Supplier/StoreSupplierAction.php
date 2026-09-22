<?php

namespace App\Actions\Cms\Supplier;

use App\Models\Supplier\Supplier;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class StoreSupplierAction
{
    public function handle(array $data): Supplier
    {
        Gate::authorize('create', Supplier::class);

        return Supplier::query()->create(Arr::only($data, [
            'code',
            'name',
            'contact_name',
            'contact_email',
            'contact_phone',
            'order_phone',
            'notes',
            'is_active',
        ]));
    }
}
