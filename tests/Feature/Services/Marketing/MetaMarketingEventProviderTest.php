<?php

use App\Models\Marketing\MarketingEvent;
use App\Services\Marketing\MetaMarketingEventProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::preventStrayRequests();

    config()->set([
        'marketing.providers.meta.pixel_id' => 'pixel-123',
        'marketing.providers.meta.access_token' => 'meta-secret',
        'marketing.providers.meta.api_version' => 'v24.0',
        'marketing.providers.meta.test_event_code' => 'TEST100',
        'marketing.providers.meta.connect_timeout' => 3,
        'marketing.providers.meta.timeout' => 10,
    ]);
});

it('sends a Meta server event with hashed customer data and the browser event id', function () {
    Http::fake([
        'https://graph.facebook.com/v24.0/pixel-123/events*' => Http::response([
            'events_received' => 1,
            'fbtrace_id' => 'trace-1001',
        ]),
    ]);
    $event = MarketingEvent::factory()->create([
        'event_id' => 'shared-event-1001',
        'name' => 'Purchase',
        'source_url' => 'https://ecommerce.test/p/example',
        'customer_data' => [
            'email' => ' Buyer@Example.COM ',
            'phone' => '+62 812-3456-7890',
            'client_ip_address' => '203.0.113.10',
            'client_user_agent' => 'Test Browser',
            'fbp' => 'fb.1.100.example',
        ],
        'custom_data' => ['currency' => 'IDR', 'value' => 150000],
        'occurred_at' => '2026-09-22 08:00:00',
    ]);

    $result = app(MetaMarketingEventProvider::class)->send($event);

    expect($result->externalId)->toBe('trace-1001');
    Http::assertSent(function (Request $request): bool {
        $event = $request->data()['data'][0];

        return $request->method() === 'POST'
            && $request->url() === 'https://graph.facebook.com/v24.0/pixel-123/events'
            && $request->hasHeader('Authorization', 'Bearer meta-secret')
            && $request['test_event_code'] === 'TEST100'
            && $event['event_name'] === 'Purchase'
            && $event['event_id'] === 'shared-event-1001'
            && $event['user_data']['em'] === [hash('sha256', 'buyer@example.com')]
            && $event['user_data']['ph'] === [hash('sha256', '6281234567890')]
            && $event['user_data']['client_ip_address'] === '203.0.113.10'
            && $event['user_data']['client_user_agent'] === 'Test Browser';
    });
    Http::assertSentCount(1);
});

it('rejects malformed successful Meta responses', function () {
    Http::fake([
        'https://graph.facebook.com/v24.0/pixel-123/events*' => Http::response(['ok' => true]),
    ]);
    $event = MarketingEvent::factory()->create();

    expect(fn () => app(MetaMarketingEventProvider::class)->send($event))
        ->toThrow(UnexpectedValueException::class, 'Meta returned an invalid event response.');

    Http::assertSentCount(1);
});
