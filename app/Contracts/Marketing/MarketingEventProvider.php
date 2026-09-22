<?php

namespace App\Contracts\Marketing;

use App\Data\Marketing\MarketingDeliveryResult;
use App\Models\Marketing\MarketingEvent;

interface MarketingEventProvider
{
    public function send(MarketingEvent $event): MarketingDeliveryResult;
}
