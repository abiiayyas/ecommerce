<?php

namespace App\Services\Notifications;

use App\Jobs\SendNotificationDelivery;
use App\Models\Notification\NotificationDelivery;
use Illuminate\Support\Str;
use InvalidArgumentException;

class NotificationDispatcher
{
    /** @param array<string, mixed> $context */
    public function sendWhatsApp(
        string $messageType,
        string $recipient,
        string $message,
        string $idempotencyKey,
        array $context = [],
        ?string $provider = null,
    ): NotificationDelivery {
        $provider ??= config('notifications.channels.whatsapp.default_provider');

        if (! is_string($provider) || blank($provider)) {
            throw new InvalidArgumentException('A WhatsApp notification provider must be configured.');
        }

        $normalizedRecipient = $this->normalizeRecipient($recipient);

        if (blank($messageType) || blank($message) || blank($idempotencyKey) || blank($normalizedRecipient)) {
            throw new InvalidArgumentException('Notification type, recipient, message, and idempotency key are required.');
        }

        $delivery = NotificationDelivery::query()->firstOrCreate(
            ['deduplication_key' => hash('sha256', $idempotencyKey)],
            [
                'channel' => 'whatsapp',
                'provider' => $provider,
                'message_type' => $messageType,
                'recipient_hash' => hash('sha256', $normalizedRecipient),
                'recipient' => $normalizedRecipient,
                'message' => $message,
                'context' => $context,
            ],
        );

        if ($delivery->wasRecentlyCreated) {
            SendNotificationDelivery::dispatch($delivery)->afterCommit();
        }

        return $delivery;
    }

    private function normalizeRecipient(string $recipient): string
    {
        $digits = (string) Str::of($recipient)->replaceMatches('/\D+/', '');

        if (Str::startsWith($digits, '0')) {
            $countryCode = (string) config('notifications.channels.whatsapp.country_code', '62');

            return $countryCode.Str::after($digits, '0');
        }

        return $digits;
    }
}
