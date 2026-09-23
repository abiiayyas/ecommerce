<?php

use App\Models\Marketing\Banner;
use Livewire\Livewire;

it('registers the ecommerce.component.banner component', function (): void {
    expect(Livewire::exists('ecommerce.component.banner'))->toBeTrue();
});

it('renders safely when no banners are active', function (): void {
    Livewire::test('ecommerce.component.banner')->assertOk();
});

it('renders active banners and hides inactive ones', function (): void {
    Banner::factory()->create([
        'title' => 'Promo Pertama',
        'sort_order' => 1,
        'is_active' => true,
    ]);
    Banner::factory()->create([
        'title' => 'Promo Nonaktif',
        'sort_order' => 2,
        'is_active' => false,
    ]);
    Banner::factory()->create([
        'title' => 'Promo Kedua',
        'sort_order' => 3,
        'is_active' => true,
    ]);

    Livewire::test('ecommerce.component.banner')
        ->assertSeeInOrder(['Promo Pertama', 'Promo Kedua'])
        ->assertDontSee('Promo Nonaktif');
});
