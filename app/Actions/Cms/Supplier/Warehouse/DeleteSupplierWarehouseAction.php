<?php

namespace App\Actions\Cms\Supplier\Warehouse;

use App\Models\Supplier\SupplierWarehouse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DeleteSupplierWarehouseAction
{
    public function handle(SupplierWarehouse $warehouse): bool
    {
        Gate::authorize('delete', $warehouse);

        if ($warehouse->offers()->exists()) {
            throw ValidationException::withMessages([
                'warehouse' => 'Gudang tidak dapat dihapus selama masih memiliki penawaran.',
            ]);
        }

        return (bool) $warehouse->delete();
    }
}
