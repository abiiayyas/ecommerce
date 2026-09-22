<?php

namespace App\Services\Marketing;

use App\Contracts\Marketing\MarketingEventPublisher;
use App\Data\Marketing\MarketingEventData;
use App\Jobs\SendMarketingEvent;
use App\Models\Marketing\MarketingEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class MarketingTracker implements MarketingEventPublisher
{
    public function newEventId(): string
    {
        return (string) Str::uuid();
    }

    public function publish(MarketingEventData $data, ?string $provider = null): MarketingEvent
    {
        $provider ??= config('marketing.default_provider');

        if (blank($data->eventId) || Str::length($data->eventId) > 100 || blank($data->name)) {
            throw new InvalidArgumentException('Marketing event id and name are required.');
        }

        if (! is_string($provider) || blank($provider)) {
            throw new InvalidArgumentException('A marketing provider must be configured.');
        }

        return DB::transaction(function () use ($data, $provider): MarketingEvent {
            $event = MarketingEvent::query()->firstOrCreate(
                ['event_id' => $data->eventId],
                [
                    'name' => $data->name,
                    'action_source' => $data->actionSource,
                    'source_url' => $data->sourceUrl,
                    'customer_data' => $data->customerData,
                    'custom_data' => $data->customData,
                    'attribution' => $data->attribution,
                    'occurred_at' => $data->occurredAt,
                ],
            );

            $delivery = $event->deliveries()->firstOrCreate(['provider' => $provider]);

            if ($delivery->wasRecentlyCreated) {
                SendMarketingEvent::dispatch($delivery)->afterCommit();
            }

            return $event;
        });
    }
}
