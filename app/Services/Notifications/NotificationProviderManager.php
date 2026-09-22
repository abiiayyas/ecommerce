<?php

namespace App\Services\Notifications;

use App\Contracts\Notifications\NotificationProvider;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class NotificationProviderManager
{
    public function __construct(
        private readonly Container $container,
    ) {}

    public function driver(string $provider): NotificationProvider
    {
        return match ($provider) {
            'fonnte' => $this->container->make(FonnteNotificationProvider::class),
            default => throw new InvalidArgumentException("Unsupported notification provider [{$provider}]."),
        };
    }
}
