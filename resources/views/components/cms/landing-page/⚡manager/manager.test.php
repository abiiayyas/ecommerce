<?php

use App\Models\Marketing\LandingPage;
use App\Models\Product\Product;
use App\Models\Spatie\Role;
use App\Models\User;
use Livewire\Livewire;

function landingManagerAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('superadmin', 'api'));

    return $admin;
}

it('renders successfully for an operator', function () {
    $this->actingAs(landingManagerAdmin());

    Livewire::test('cms.landing-page.manager')->assertStatus(200);
});

it('creates a draft from an active product', function () {
    $this->actingAs(landingManagerAdmin());
    $product = Product::factory()->hasProductFlats(1, ['status' => true])->create(['status' => true]);

    Livewire::test('cms.landing-page.manager')
        ->call('create')
        ->assertRedirect(route('cms.landing-page.builder', ['id' => LandingPage::query()->latest('id')->value('id')]));

    expect(LandingPage::query()->where('product_id', $product->id)->where('is_active', false)->exists())->toBeTrue();
});
