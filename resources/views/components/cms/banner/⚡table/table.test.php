<?php

use App\Models\Marketing\Banner;
use App\Models\Spatie\Role;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    Role::findOrCreate('superadmin', 'api');
    Role::findOrCreate('user', 'api');
});

it('registers the banner table component', function (): void {
    expect(Livewire::exists('cms.banner.table'))->toBeTrue();
});

it('lists banners and allows a superadmin to delete them', function (): void {
    $user = User::factory()->create();
    $user->assignRole('superadmin');
    $banner = Banner::factory()->create(['title' => 'Banner Yang Dihapus']);

    Livewire::actingAs($user)
        ->test('cms.banner.table')
        ->assertSee($banner->title)
        ->call('delete', $banner->id)
        ->assertDispatched('toast', type: 'success', message: 'Banner berhasil dihapus.');

    expect($banner->fresh())->toBeNull();
});

it('rejects non-admin banner table access', function (): void {
    $user = User::factory()->create();
    $user->assignRole('user');

    Livewire::actingAs($user)
        ->test('cms.banner.table')
        ->assertForbidden();
});
