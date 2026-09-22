<?php

namespace App\Actions\Cms\Supplier;

use App\Models\Supplier\Supplier;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DeleteSupplierAction
{
    public function handle(Supplier $supplier): bool
    {
        Gate::authorize('delete', $supplier);

        if ($supplier->warehouses()->exists() || $supplier->offers()->exists()) {
            throw ValidationException::withMessages([
                'supplier' => 'Supplier tidak dapat dihapus selama masih memiliki gudang atau penawaran.',
            ]);
        }

        return (bool) $supplier->delete();
    }
}
