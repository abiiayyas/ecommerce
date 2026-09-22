<?php

namespace App\Contracts\Marketing;

use App\Data\Marketing\MarketingEventData;
use App\Models\Marketing\MarketingEvent;

interface MarketingEventPublisher
{
    public function newEventId(): string;

    public function publish(MarketingEventData $data, ?string $provider = null): MarketingEvent;
}
