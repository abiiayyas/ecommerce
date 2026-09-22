<?php

namespace App\Contracts\Notifications;

use App\Data\Notifications\NotificationDeliveryResult;
use App\Models\Notification\NotificationDelivery;

interface NotificationProvider
{
    public function send(NotificationDelivery $delivery): NotificationDeliveryResult;
}
