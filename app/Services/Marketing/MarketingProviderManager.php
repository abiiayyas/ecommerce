<?php

namespace App\Services\Marketing;

use App\Contracts\Marketing\MarketingEventProvider;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class MarketingProviderManager
{
    public function __construct(
        private readonly Container $container,
    ) {}

    public function driver(?string $provider = null): MarketingEventProvider
    {
        $provider ??= config('marketing.default_provider');

        if (! is_string($provider)) {
            throw new InvalidArgumentException('A marketing provider must be configured.');
        }

        return match ($provider) {
            'meta' => $this->container->make(MetaMarketingEventProvider::class),
            default => throw new InvalidArgumentException("Unsupported marketing provider [{$provider}]."),
        };
    }
}
