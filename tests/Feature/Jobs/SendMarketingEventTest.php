<?php

use App\Jobs\SendMarketingEvent;
use App\Models\Marketing\MarketingEventDelivery;
use App\Services\Marketing\MarketingProviderManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('marks a Meta marketing delivery as sent after the provider accepts it', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://graph.facebook.com/v24.0/pixel-123/events*' => Http::response([
            'events_received' => 1,
            'fbtrace_id' => 'trace-1001',
        ]),
    ]);
    config()->set([
        'marketing.providers.meta.pixel_id' => 'pixel-123',
        'marketing.providers.meta.access_token' => 'meta-secret',
        'marketing.providers.meta.api_version' => 'v24.0',
        'marketing.providers.meta.connect_timeout' => 3,
        'marketing.providers.meta.timeout' => 10,
    ]);
    $delivery = MarketingEventDelivery::factory()->create();

    (new SendMarketingEvent($delivery))->handle(app(MarketingProviderManager::class));

    $delivery->refresh();
    expect($delivery->status)->toBe(MarketingEventDelivery::STATUS_SENT)
        ->and($delivery->attempts)->toBe(1)
        ->and($delivery->external_id)->toBe('trace-1001')
        ->and($delivery->delivered_at)->not->toBeNull();
});
