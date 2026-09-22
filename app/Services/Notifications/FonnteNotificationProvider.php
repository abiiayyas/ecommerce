<?php

namespace App\Services\Notifications;

use App\Contracts\Notifications\NotificationProvider;
use App\Data\Notifications\NotificationDeliveryResult;
use App\Models\Notification\NotificationDelivery;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use LogicException;
use UnexpectedValueException;

class FonnteNotificationProvider implements NotificationProvider
{
    public function send(NotificationDelivery $delivery): NotificationDeliveryResult
    {
        $token = config('notifications.providers.fonnte.token');

        if (! is_string($token) || blank($token)) {
            throw new LogicException('Fonnte credentials are not configured.');
        }

        $response = Http::asForm()
            ->acceptJson()
            ->withHeaders(['Authorization' => $token])
            ->connectTimeout((int) config('notifications.providers.fonnte.connect_timeout'))
            ->timeout((int) config('notifications.providers.fonnte.timeout'))
            ->post((string) config('notifications.providers.fonnte.base_url'), [
                'target' => $delivery->recipient,
                'message' => $delivery->message,
                'countryCode' => (string) config('notifications.channels.whatsapp.country_code'),
            ])
            ->throw();

        $body = $response->json();

        if (! is_array($body) || Arr::get($body, 'status') !== true) {
            throw new UnexpectedValueException('Fonnte rejected the notification.');
        }

        $externalId = Arr::get($body, 'id.0', Arr::get($body, 'id'));

        return new NotificationDeliveryResult(
            externalId: is_scalar($externalId) ? (string) $externalId : null,
            response: $body,
        );
    }
}
