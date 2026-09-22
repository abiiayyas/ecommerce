<?php

use App\Actions\Ecommerce\Checkout\CreateLandingOrderAction;
use App\Enums\SalesChannel;
use App\Jobs\SendMarketingEvent;
use App\Jobs\SendNotificationDelivery;
use App\Models\Marketing\LandingPage;
use App\Models\Product\ProductFlat;
use App\Models\Shipping\ShippingRateQuote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('creates an ad landing order and consumes its server-side shipping quote', function () {
    Queue::fake();
    $flat = ProductFlat::factory()->create(['price' => 100_000]);
    $landingPage = LandingPage::factory()->create(['product_id' => $flat->product_id]);
    $areaId = 'area-123';
    $quote = ShippingRateQuote::factory()->create([
        'landing_page_id' => $landingPage->id,
        'product_flat_id' => $flat->id,
        'quantity' => 1,
        'destination_area_id' => $areaId,
        'payload_hash' => CreateLandingOrderAction::quotePayloadHash($landingPage->id, $flat->id, 1, $areaId),
    ]);

    $order = app(CreateLandingOrderAction::class)->handle($landingPage, [
        'product_flat_id' => $flat->id,
        'quantity' => 1,
        'shipping_rate_quote_id' => $quote->public_id,
        'payment_mode' => 'online',
        'contact_name' => 'Test Buyer',
        'contact_phone' => '08123456789',
        'contact_email' => 'buyer@example.test',
        'address' => 'Jalan Test 1',
        'postal_code' => '40111',
        'area_string' => 'Bandung',
        'destination_area_id' => $areaId,
    ]);

    expect($order->sales_channel)->toBe(SalesChannel::AdLanding)
        ->and($order->stockReservations)->toHaveCount(1)
        ->and($quote->refresh()->consumed_at)->not->toBeNull();
    Queue::assertPushed(SendMarketingEvent::class);
    Queue::assertPushed(SendNotificationDelivery::class);
});
