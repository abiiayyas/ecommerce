<?php

namespace App\Policies\Supplier;

use App\Models\Supplier\SupplierWarehouse;
use App\Models\User;

class SupplierWarehousePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allowed($user, 'view');
    }

    public function view(User $user, SupplierWarehouse $supplierWarehouse): bool
    {
        return $this->allowed($user, 'show');
    }

    public function create(User $user): bool
    {
        return $this->allowed($user, 'create');
    }

    public function update(User $user, SupplierWarehouse $supplierWarehouse): bool
    {
        return $this->allowed($user, 'update');
    }

    public function delete(User $user, SupplierWarehouse $supplierWarehouse): bool
    {
        return $this->allowed($user, 'delete');
    }

    private function allowed(User $user, string $ability): bool
    {
        return $user->hasRole('superadmin')
            || $user->getAllPermissions()->contains('name', $ability.SupplierWarehouse::class);
    }
}
