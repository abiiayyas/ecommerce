<?php

namespace App\Policies\Supplier;

use App\Models\Supplier\SupplierOffer;
use App\Models\User;

class SupplierOfferPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allowed($user, 'view');
    }

    public function view(User $user, SupplierOffer $supplierOffer): bool
    {
        return $this->allowed($user, 'show');
    }

    public function create(User $user): bool
    {
        return $this->allowed($user, 'create');
    }

    public function update(User $user, SupplierOffer $supplierOffer): bool
    {
        return $this->allowed($user, 'update');
    }

    public function delete(User $user, SupplierOffer $supplierOffer): bool
    {
        return $this->allowed($user, 'delete');
    }

    private function allowed(User $user, string $ability): bool
    {
        return $user->hasRole('superadmin')
            || $user->getAllPermissions()->contains('name', $ability.SupplierOffer::class);
    }
}
