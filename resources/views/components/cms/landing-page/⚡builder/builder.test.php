<?php

use App\Models\Marketing\LandingPage;
use App\Models\Product\Product;
use App\Models\Spatie\Role;
use App\Models\User;
use Livewire\Livewire;

function builderAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('superadmin', 'api'));

    return $admin;
}

it('persists a sanitized bounded layout for an operator', function () {
    $this->actingAs(builderAdmin());
    $product = Product::factory()->create(['status' => true]);
    $page = LandingPage::factory()->draft()->create(['product_id' => $product->id]);

    Livewire::test('cms.landing-page.builder', ['landingPage' => $page])
        ->set('productId', $product->id)
        ->set('slug', 'campaign-page')
        ->set('headline', 'Campaign page')
        ->set('onlinePaymentEnabled', true)
        ->set('codEnabled', false)
        ->call('save', [
            'layout' => 'fullwidth',
            'radius' => 999,
            'font' => 'Inter',
            'paddingY' => 20,
            'paddingX' => 24,
            'marginY' => 16,
            'marginX' => 0,
            'componentMargin' => 12,
            'blocks' => [[
                'id' => 'block_safe',
                'type' => 'text',
                'content' => '<script>alert(1)</script>Headline',
            ]],
        ]);

    $page->refresh();

    expect($page->slug)->toBe('campaign-page')
        ->and($page->content['builder']['radius'])->toBe(48)
        ->and($page->content['builder']['blocks'][0]['content'])->toBe('Headline');
});
