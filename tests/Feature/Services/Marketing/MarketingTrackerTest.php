<?php

use App\Contracts\Marketing\MarketingEventPublisher;
use App\Data\Marketing\MarketingEventData;
use App\Jobs\SendMarketingEvent;
use App\Models\Marketing\MarketingEvent;
use App\Models\Marketing\MarketingEventDelivery;
use App\Services\Marketing\MarketingTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('stores one event and queues one server delivery for a shared browser event id', function () {
    Queue::fake([SendMarketingEvent::class]);
    config()->set('marketing.default_provider', 'meta');

    $data = new MarketingEventData(
        eventId: 'browser-server-event-1001',
        name: 'InitiateCheckout',
        occurredAt: now(),
        sourceUrl: 'https://ecommerce.test/p/example',
        customerData: ['email' => 'buyer@example.com'],
        customData: ['currency' => 'IDR', 'value' => 150000],
        attribution: ['utm_campaign' => 'launch'],
    );

    $firstEvent = app(MarketingTracker::class)->publish($data);
    $secondEvent = app(MarketingTracker::class)->publish($data);

    expect($secondEvent->is($firstEvent))->toBeTrue()
        ->and(MarketingEvent::query()->count())->toBe(1)
        ->and(MarketingEventDelivery::query()->count())->toBe(1)
        ->and($firstEvent->customer_data)->toBe(['email' => 'buyer@example.com'])
        ->and(DB::table('marketing_events')->value('customer_data'))->not->toContain('buyer@example.com');

    Queue::assertPushed(
        SendMarketingEvent::class,
        1,
    );
});

it('generates event ids suitable for sharing with browser tracking', function () {
    $eventId = app(MarketingEventPublisher::class)->newEventId();

    expect($eventId)->toBeUuid();
});

it('rejects an event without an id before storing or queueing it', function () {
    Queue::fake([SendMarketingEvent::class]);

    expect(fn () => app(MarketingTracker::class)->publish(new MarketingEventData(
        eventId: '',
        name: 'ViewContent',
        occurredAt: now(),
    )))->toThrow(InvalidArgumentException::class, 'Marketing event id and name are required.');

    expect(MarketingEvent::query()->count())->toBe(0);
    Queue::assertNothingPushed();
});
