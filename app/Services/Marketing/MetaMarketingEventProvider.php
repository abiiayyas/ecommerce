<?php

namespace App\Services\Marketing;

use App\Contracts\Marketing\MarketingEventProvider;
use App\Data\Marketing\MarketingDeliveryResult;
use App\Models\Marketing\MarketingEvent;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use LogicException;
use UnexpectedValueException;

class MetaMarketingEventProvider implements MarketingEventProvider
{
    public function send(MarketingEvent $event): MarketingDeliveryResult
    {
        $pixelId = config('marketing.providers.meta.pixel_id');
        $accessToken = config('marketing.providers.meta.access_token');
        $apiVersion = config('marketing.providers.meta.api_version');

        if (! is_string($pixelId) || blank($pixelId) || ! is_string($accessToken) || blank($accessToken)) {
            throw new LogicException('Meta marketing credentials are not configured.');
        }

        $payload = [
            'data' => [[
                'event_name' => $event->name,
                'event_time' => $event->occurred_at->getTimestamp(),
                'event_id' => $event->event_id,
                'action_source' => $event->action_source,
                'event_source_url' => $event->source_url,
                'user_data' => $this->normalizeCustomerData($event->customer_data ?? []),
                'custom_data' => $event->custom_data ?? [],
            ]],
        ];

        $testEventCode = config('marketing.providers.meta.test_event_code');

        if (is_string($testEventCode) && filled($testEventCode)) {
            $payload['test_event_code'] = $testEventCode;
        }

        $response = Http::baseUrl("https://graph.facebook.com/{$apiVersion}")
            ->acceptJson()
            ->withToken($accessToken)
            ->connectTimeout((int) config('marketing.providers.meta.connect_timeout'))
            ->timeout((int) config('marketing.providers.meta.timeout'))
            ->post("/{$pixelId}/events", $payload)
            ->throw();

        $body = $response->json();

        if (! is_array($body) || ! is_numeric(Arr::get($body, 'events_received'))) {
            throw new UnexpectedValueException('Meta returned an invalid event response.');
        }

        return new MarketingDeliveryResult(
            externalId: is_string(Arr::get($body, 'fbtrace_id')) ? Arr::get($body, 'fbtrace_id') : null,
            response: $body,
        );
    }

    /** @param array<string, mixed> $customerData */
    private function normalizeCustomerData(array $customerData): array
    {
        $normalized = Arr::only($customerData, [
            'client_ip_address',
            'client_user_agent',
            'fbp',
            'fbc',
        ]);

        foreach (['email' => 'em', 'phone' => 'ph', 'external_id' => 'external_id'] as $input => $output) {
            $value = Arr::get($customerData, $input);

            if (! is_scalar($value) || blank((string) $value)) {
                continue;
            }

            $normalized[$output] = [hash('sha256', $this->normalizeHashableValue($input, (string) $value))];
        }

        return array_filter($normalized, static fn (mixed $value): bool => filled($value));
    }

    private function normalizeHashableValue(string $field, string $value): string
    {
        $value = (string) Str::of($value)->trim()->lower();

        if ($field === 'phone') {
            return (string) Str::of($value)->replaceMatches('/\D+/', '');
        }

        return $value;
    }
}
