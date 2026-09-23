<?php

use App\Models\Marketing\Banner;
use App\Models\Spatie\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    Storage::fake(config('media-library.disk_name'));
    Role::findOrCreate('superadmin', 'api');
});

it('creates and updates a banner with its configured storefront content', function (): void {
    $user = User::factory()->create();
    $user->assignRole('superadmin');

    Livewire::actingAs($user)
        ->test('cms.banner.create-update')
        ->set('title', 'Promo Akhir Pekan')
        ->set('description', 'Diskon pilihan untuk akhir pekan.')
        ->set('button_text', 'Lihat Promo')
        ->set('button_url', '/explore')
        ->set('sort_order', 2)
        ->set('is_active', true)
        ->set('image', UploadedFile::fake()->image('promo.jpg', 1920, 700))
        ->call('submit')
        ->assertHasNoErrors()
        ->assertDispatched('toast', type: 'success', message: 'Banner berhasil dibuat.');

    $banner = Banner::query()->where('title', 'Promo Akhir Pekan')->sole();

    expect($banner->description)->toBe('Diskon pilihan untuk akhir pekan.')
        ->and($banner->button_text)->toBe('Lihat Promo')
        ->and($banner->button_url)->toBe('/explore')
        ->and($banner->sort_order)->toBe(2)
        ->and($banner->is_active)->toBeTrue()
        ->and($banner->getFirstMediaUrl('image'))->not->toBeEmpty();

    Livewire::actingAs($user)
        ->test('cms.banner.create-update')
        ->call('setAction', $banner->id)
        ->set('title', 'Promo Diperbarui')
        ->set('is_active', false)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertDispatched('toast', type: 'success', message: 'Banner berhasil diperbarui.');

    expect($banner->fresh()->title)->toBe('Promo Diperbarui')
        ->and($banner->fresh()->is_active)->toBeFalse()
        ->and($banner->fresh()->getFirstMediaUrl('image'))->not->toBeEmpty();
});

it('rejects unsafe banner links', function (): void {
    $user = User::factory()->create();
    $user->assignRole('superadmin');

    Livewire::actingAs($user)
        ->test('cms.banner.create-update')
        ->set('title', 'Banner Aman')
        ->set('button_url', 'javascript:alert(1)')
        ->set('image', UploadedFile::fake()->image('promo.jpg', 1920, 700))
        ->call('submit')
        ->assertHasErrors(['button_url']);
});
