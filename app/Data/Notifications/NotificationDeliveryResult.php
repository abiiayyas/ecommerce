<?php

namespace App\Data\Notifications;

class NotificationDeliveryResult
{
    /** @param array<string, mixed> $response */
    public function __construct(
        public readonly ?string $externalId,
        public readonly array $response,
    ) {}
}
