<?php

namespace App\Data\Marketing;

use DateTimeInterface;

class MarketingEventData
{
    /**
     * @param  array<string, mixed>  $customerData
     * @param  array<string, mixed>  $customData
     * @param  array<string, mixed>  $attribution
     */
    public function __construct(
        public readonly string $eventId,
        public readonly string $name,
        public readonly DateTimeInterface $occurredAt,
        public readonly string $actionSource = 'website',
        public readonly ?string $sourceUrl = null,
        public readonly array $customerData = [],
        public readonly array $customData = [],
        public readonly array $attribution = [],
    ) {}
}
