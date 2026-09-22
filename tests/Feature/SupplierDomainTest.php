<?php

use App\Actions\Cms\Product\Product\SetProductFulfillmentAction;
use App\Actions\Cms\Supplier\Offer\DeleteSupplierOfferAction;
use App\Actions\Cms\Supplier\Offer\StoreSupplierOfferAction;
use App\Actions\Cms\Supplier\StoreSupplierAction;
use App\Enums\FulfillmentType;
use App\Models\Product\Product;
use App\Models\Product\ProductFlat;
use App\Models\Spatie\Permission;
use App\Models\Spatie\Role;
use App\Models\Supplier\Supplier;
use App\Models\Supplier\SupplierOffer;
use App\Models\Supplier\SupplierWarehouse;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function supplierAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('superadmin', 'api'));

    return $admin;
}

function grantProductUpdatePermission(User $user): void
{
    $permission = Permission::findOrCreate('update'.Product::class, 'api');
    $user->givePermissionTo($permission);
}

it('keeps existing product variants on owned stock by default', function () {
    $productFlat = ProductFlat::factory()->create();

    expect($productFlat->fulfillment_type)->toBe(FulfillmentType::OwnedStock)
        ->and($productFlat->active_supplier_offer_id)->toBeNull()
        ->and($productFlat->activeSupplierOffer)->toBeNull();
});

it('enforces a fulfillment source for every product variant', function (array $attributes) {
    ProductFlat::factory()->create($attributes);
})->with([
    'owned stock with supplier offer' => [[
        'fulfillment_type' => FulfillmentType::OwnedStock,
        'active_supplier_offer_id' => 1,
    ]],
    'dropship without supplier offer' => [[
        'fulfillment_type' => FulfillmentType::SupplierDropship,
        'active_supplier_offer_id' => null,
    ]],
])->throws(ValidationException::class);

it('stores provider-neutral supplier warehouse origin references', function () {
    $warehouse = SupplierWarehouse::factory()->create([
        'primary_shipping_provider' => 'mengantar',
        'fallback_shipping_provider' => 'biteship',
        'provider_area_ids' => [
            'mengantar' => 'JKT-01',
            'biteship' => 'IDCGK101',
        ],
        'provider_address_ids' => ['mengantar' => 'pickup-123'],
    ]);

    expect($warehouse->provider_area_ids)->toBe([
        'mengantar' => 'JKT-01',
        'biteship' => 'IDCGK101',
    ])->and($warehouse->provider_address_ids)->toBe(['mengantar' => 'pickup-123'])
        ->and($warehouse->supplier)->toBeInstanceOf(Supplier::class);
});

it('allows internal admins to create suppliers', function () {
    $this->actingAs(supplierAdmin());

    $supplier = app(StoreSupplierAction::class)->handle([
        'code' => 'ANEKA',
        'name' => 'Aneka Dropship',
        'contact_phone' => '081234567890',
    ]);

    expect($supplier->exists)->toBeTrue()
        ->and($supplier->is_active)->toBeTrue()
        ->and($supplier->code)->toBe('ANEKA');
});

it('rejects supplier management by regular users', function () {
    $this->actingAs(User::factory()->create());

    app(StoreSupplierAction::class)->handle([
        'code' => 'BLOCKED',
        'name' => 'Blocked Supplier',
    ]);
})->throws(AuthorizationException::class);

it('rejects warehouses owned by another supplier when creating an offer', function () {
    $this->actingAs(supplierAdmin());

    $supplier = Supplier::factory()->create();
    $otherWarehouse = SupplierWarehouse::factory()->create();

    app(StoreSupplierOfferAction::class)->handle(
        $supplier,
        $otherWarehouse,
        ProductFlat::factory()->create(),
        [
            'supplier_sku' => 'INVALID-01',
            'cost_price' => 50_000,
        ],
    );
})->throws(ValidationException::class);

it('selects exactly one eligible supplier offer as the fulfillment source', function () {
    $admin = supplierAdmin();
    grantProductUpdatePermission($admin);
    $this->actingAs($admin);

    $productFlat = ProductFlat::factory()->create();
    $selectedOffer = SupplierOffer::factory()->create(['product_flat_id' => $productFlat->id]);
    SupplierOffer::factory()->create(['product_flat_id' => $productFlat->id]);

    $updatedProductFlat = app(SetProductFulfillmentAction::class)->handle(
        $productFlat,
        FulfillmentType::SupplierDropship,
        $selectedOffer,
    );

    expect($updatedProductFlat->fulfillment_type)->toBe(FulfillmentType::SupplierDropship)
        ->and($updatedProductFlat->active_supplier_offer_id)->toBe($selectedOffer->id)
        ->and($updatedProductFlat->activeSupplierOffer->is($selectedOffer))->toBeTrue();
});

it('rejects an offer belonging to another variant', function () {
    $admin = supplierAdmin();
    grantProductUpdatePermission($admin);
    $this->actingAs($admin);

    app(SetProductFulfillmentAction::class)->handle(
        ProductFlat::factory()->create(),
        FulfillmentType::SupplierDropship,
        SupplierOffer::factory()->create(),
    );
})->throws(ValidationException::class);

it('rejects an unavailable supplier offer', function () {
    $admin = supplierAdmin();
    grantProductUpdatePermission($admin);
    $this->actingAs($admin);

    $productFlat = ProductFlat::factory()->create();

    app(SetProductFulfillmentAction::class)->handle(
        $productFlat,
        FulfillmentType::SupplierDropship,
        SupplierOffer::factory()->unavailable()->create(['product_flat_id' => $productFlat->id]),
    );
})->throws(ValidationException::class);

it('clears the active supplier offer when switching back to owned stock', function () {
    $admin = supplierAdmin();
    grantProductUpdatePermission($admin);
    $this->actingAs($admin);

    $productFlat = ProductFlat::factory()->create();
    $offer = SupplierOffer::factory()->create(['product_flat_id' => $productFlat->id]);
    $productFlat->update([
        'fulfillment_type' => FulfillmentType::SupplierDropship,
        'active_supplier_offer_id' => $offer->id,
    ]);

    $updatedProductFlat = app(SetProductFulfillmentAction::class)->handle(
        $productFlat,
        FulfillmentType::OwnedStock,
    );

    expect($updatedProductFlat->fulfillment_type)->toBe(FulfillmentType::OwnedStock)
        ->and($updatedProductFlat->active_supplier_offer_id)->toBeNull();
});

it('prevents deleting an offer selected by a product variant', function () {
    $this->actingAs(supplierAdmin());

    $productFlat = ProductFlat::factory()->create();
    $offer = SupplierOffer::factory()->create(['product_flat_id' => $productFlat->id]);
    $productFlat->update([
        'fulfillment_type' => FulfillmentType::SupplierDropship,
        'active_supplier_offer_id' => $offer->id,
    ]);

    app(DeleteSupplierOfferAction::class)->handle($offer);
})->throws(ValidationException::class);
