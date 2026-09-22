<?php

namespace App\Jobs;

use App\Models\Notification\NotificationDelivery;
use App\Services\Notifications\NotificationProviderManager;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendNotificationDelivery implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 15;

    public int $uniqueFor = 600;

    public function __construct(
        public NotificationDelivery $delivery,
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

    public function handle(NotificationProviderManager $manager): void
    {
        $delivery = $this->delivery->fresh();

        if ($delivery === null || $delivery->status === NotificationDelivery::STATUS_SENT) {
            return;
        }

        $delivery->increment('attempts');

        try {
            $result = $manager->driver($delivery->provider)->send($delivery);

            $delivery->update([
                'status' => NotificationDelivery::STATUS_SENT,
                'external_id' => $result->externalId,
                'response' => $result->response,
                'last_error' => null,
                'sent_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $delivery->update(['last_error' => $exception->getMessage()]);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->delivery->newQuery()->whereKey($this->delivery->getKey())->update([
            'status' => NotificationDelivery::STATUS_FAILED,
            'last_error' => $exception?->getMessage(),
        ]);
    }
}
