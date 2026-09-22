<?php

namespace App\Data\Marketing;

class MarketingDeliveryResult
{
    /** @param array<string, mixed> $response */
    public function __construct(
        public readonly ?string $externalId,
        public readonly array $response,
    ) {}
}
