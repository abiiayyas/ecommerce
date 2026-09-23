<?php

use App\Enums\FulfillmentType;
use App\Models\Spatie\Permission;
use App\Models\Spatie\Role;
use App\Models\Product\ProductFlat;
use App\Models\Supplier\SupplierOffer;
use App\Models\Product\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function supplierManagerAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('superadmin', 'api'));
    $admin->givePermissionTo(Permission::findOrCreate('update' . Product::class, 'api'));

    return $admin;
}

it('renders supplier management for an operator', function () {
    $this->actingAs(supplierManagerAdmin());

    Livewire::test('cms.supplier.manager')->assertStatus(200);
});

it('deactivates the selected dropship source and returns the variant to owned stock', function () {
    $this->actingAs(supplierManagerAdmin());
    $productFlat = ProductFlat::factory()->create();
    $offer = SupplierOffer::factory()->create(['product_flat_id' => $productFlat->id]);
    $productFlat->update([
        'fulfillment_type' => FulfillmentType::SupplierDropship,
        'active_supplier_offer_id' => $offer->id,
    ]);

    Livewire::test('cms.supplier.manager')
        ->call('deactivateOffer', $offer->id);

    expect($productFlat->refresh()->fulfillment_type)->toBe(FulfillmentType::OwnedStock)
        ->and($productFlat->active_supplier_offer_id)->toBeNull()
        ->and($offer->refresh()->is_active)->toBeFalse()
        ->and($offer->is_available)->toBeFalse();
});

it('deletes an active dropship source after detaching its fulfillment', function () {
    $this->actingAs(supplierManagerAdmin());
    $productFlat = ProductFlat::factory()->create();
    $offer = SupplierOffer::factory()->create(['product_flat_id' => $productFlat->id]);
    $productFlat->update([
        'fulfillment_type' => FulfillmentType::SupplierDropship,
        'active_supplier_offer_id' => $offer->id,
    ]);

    Livewire::test('cms.supplier.manager')
        ->call('deleteOffer', $offer->id);

    expect(SupplierOffer::query()->whereKey($offer->id)->exists())->toBeFalse()
        ->and($productFlat->refresh()->fulfillment_type)->toBe(FulfillmentType::OwnedStock)
        ->and($productFlat->active_supplier_offer_id)->toBeNull();
});
