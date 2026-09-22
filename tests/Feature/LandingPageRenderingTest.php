<?php

use App\Jobs\SendMarketingEvent;
use App\Models\Marketing\LandingPage;
use App\Models\Product\ProductFlat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('renders a published landing page and records a daily visit', function () {
    Queue::fake();
    $flat = ProductFlat::factory()->create();
    $landingPage = LandingPage::factory()->create(['product_id' => $flat->product_id]);

    $response = $this->get(route('landing.show', ['slug' => $landingPage->slug]));

    $response->assertOk()->assertSee($landingPage->headline);
    expect($landingPage->dailyStats()->first()->page_views)->toBe(1);
    Queue::assertPushed(SendMarketingEvent::class);
});

it('does not expose an unpublished landing page', function () {
    $flat = ProductFlat::factory()->create();
    $landingPage = LandingPage::factory()->draft()->create(['product_id' => $flat->product_id]);

    $this->get(route('landing.show', ['slug' => $landingPage->slug]))->assertNotFound();
});
