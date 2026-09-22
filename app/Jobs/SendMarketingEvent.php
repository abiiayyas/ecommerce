<?php

namespace App\Jobs;

use App\Models\Marketing\MarketingEventDelivery;
use App\Services\Marketing\MarketingProviderManager;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendMarketingEvent implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 15;

    public int $uniqueFor = 600;

    public function __construct(
        public MarketingEventDelivery $delivery,
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->delivery->getKey();
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(MarketingProviderManager $manager): void
    {
        $delivery = $this->delivery->fresh(['marketingEvent']);

        if ($delivery === null || $delivery->status === MarketingEventDelivery::STATUS_SENT) {
            return;
        }

        $delivery->increment('attempts');

        try {
            $result = $manager->driver($delivery->provider)->send($delivery->marketingEvent);

            $delivery->update([
                'status' => MarketingEventDelivery::STATUS_SENT,
                'external_id' => $result->externalId,
                'response' => $result->response,
                'last_error' => null,
                'delivered_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $delivery->update(['last_error' => $exception->getMessage()]);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->delivery->newQuery()->whereKey($this->delivery->getKey())->update([
            'status' => MarketingEventDelivery::STATUS_FAILED,
            'last_error' => $exception?->getMessage(),
        ]);
    }
}
