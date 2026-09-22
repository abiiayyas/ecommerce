<?php

namespace App\Services\Shipping;

use App\Contracts\Shipping\ShippingProvider;
use App\Data\Shipping\CreateShipmentData;
use App\Data\Shipping\ShipmentData;
use App\Data\Shipping\ShippingItemData;
use App\Data\Shipping\ShippingRateData;
use App\Data\Shipping\ShippingRateRequestData;
use App\Enums\ShippingProviderDriver;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Throwable;

class ShippingManager
{
    public function __construct(
        private readonly Container $container,
    ) {}

    public function driver(ShippingProviderDriver|string $driver): ShippingProvider
    {
        if (is_string($driver)) {
            $resolvedDriver = ShippingProviderDriver::tryFrom($driver);

            if ($resolvedDriver === null) {
                throw new InvalidArgumentException("Unsupported shipping provider [{$driver}].");
            }

            $driver = $resolvedDriver;
        }

        return match ($driver) {
            ShippingProviderDriver::Biteship => $this->container->make(BiteshipShippingProvider::class),
            ShippingProviderDriver::Mengantar => $this->container->make(MengantarShippingProvider::class),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{provider: string, courier_company: string, courier_name: string, courier_service: string, description: string|null, price: int, supports_cod: bool, raw: array<string, mixed>}>
     */
    public function rates(string $primaryProvider, ?string $fallbackProvider, array $payload): array
    {
        $request = new ShippingRateRequestData(
            originAreaId: $this->requiredString($payload, 'origin_area_id'),
            destinationAreaId: $this->requiredString($payload, 'destination_area_id'),
            items: $this->items(Arr::get($payload, 'items')),
            courierCodes: $this->courierCodes(Arr::get($payload, 'couriers', [])),
            codAmount: $this->nullableInteger(Arr::get($payload, 'cod_amount')),
        );

        $rates = $this->withFallback(
            $primaryProvider,
            $fallbackProvider,
            fn (ShippingProvider $provider): array => $provider->getRates($request),
        );

        return array_map(fn (ShippingRateData $rate): array => [
            'provider' => $rate->provider->value,
            'courier_company' => $rate->courierCode,
            'courier_name' => $rate->courierName,
            'courier_service' => $rate->serviceCode,
            'description' => $rate->duration ?? $rate->serviceName,
            'price' => $rate->price,
            'supports_cod' => $rate->supportsCod,
            'raw' => $rate->raw,
        ], $rates);
    }

    /**
     * @return list<array{provider: string, id: string, name: string, province: string|null, city: string|null, postal_code: string|null, raw: array<string, mixed>}>
     */
    public function searchAreas(string $primaryProvider, ?string $fallbackProvider, string $query): array
    {
        if (blank($query)) {
            return [];
        }

        $areas = $this->withFallback(
            $primaryProvider,
            $fallbackProvider,
            fn (ShippingProvider $provider): array => $provider->searchAreas($query),
        );

        return array_map(fn ($area): array => [
            'provider' => $area->provider->value,
            'id' => $area->id,
            'name' => $area->name,
            'province' => $area->province,
            'city' => $area->city,
            'postal_code' => $area->postalCode,
            'raw' => $area->raw,
        ], $areas);
    }

    /** @param array<string, mixed> $payload */
    public function createShipment(string $provider, array $payload): ShipmentData
    {
        $shipment = new CreateShipmentData(
            reference: $this->requiredString($payload, 'reference'),
            origin: $this->requiredArray($payload, 'origin'),
            destination: $this->requiredArray($payload, 'destination'),
            items: $this->items(Arr::get($payload, 'items')),
            courierCode: $this->requiredString($payload, 'courier_company'),
            courierService: $this->requiredString($payload, 'courier_service'),
            codAmount: $this->nullableInteger(Arr::get($payload, 'cod_amount')),
            metadata: $this->arrayValue(Arr::get($payload, 'metadata', []), 'metadata'),
        );

        return $this->driver($provider)->createShipment($shipment);
    }

    public function trackShipment(
        string $provider,
        string $externalId,
        ?string $trackingNumber = null,
        ?string $courierCode = null,
    ): ShipmentData {
        return $this->driver($provider)->trackShipment($externalId, $trackingNumber, $courierCode);
    }

    /** @return list<mixed> */
    private function withFallback(string $primary, ?string $fallback, callable $callback): array
    {
        try {
            $result = $callback($this->driver($primary));

            if ($result !== [] || blank($fallback) || $fallback === $primary) {
                return $result;
            }
        } catch (Throwable $exception) {
            if (blank($fallback) || $fallback === $primary) {
                throw $exception;
            }
        }

        return $callback($this->driver($fallback));
    }

    /** @return list<ShippingItemData> */
    private function items(mixed $items): array
    {
        if (! is_array($items) || $items === []) {
            throw new InvalidArgumentException('Shipping items are required.');
        }

        return array_map(function (mixed $item): ShippingItemData {
            if (! is_array($item)) {
                throw new InvalidArgumentException('Each shipping item must be an array.');
            }

            return new ShippingItemData(
                name: $this->requiredString($item, 'name'),
                value: $this->requiredInteger($item, 'value'),
                weightGrams: $this->requiredInteger($item, 'weight'),
                quantity: $this->nullableInteger(Arr::get($item, 'quantity')) ?? 1,
                description: $this->nullableString(Arr::get($item, 'description')),
            );
        }, array_values($items));
    }

    /** @return list<string> */
    private function courierCodes(mixed $couriers): array
    {
        if (is_string($couriers)) {
            $couriers = explode(',', $couriers);
        }

        if (! is_array($couriers)) {
            throw new InvalidArgumentException('Couriers must be a comma-separated string or an array.');
        }

        return collect($couriers)
            ->filter(fn (mixed $courier): bool => is_scalar($courier) && filled((string) $courier))
            ->map(fn (mixed $courier): string => trim((string) $courier))
            ->values()
            ->all();
    }

    /** @param array<string, mixed> $payload */
    private function requiredString(array $payload, string $key): string
    {
        $value = Arr::get($payload, $key);

        if (! is_scalar($value) || blank((string) $value)) {
            throw new InvalidArgumentException("Shipping field [{$key}] is required.");
        }

        return (string) $value;
    }

    /** @param array<string, mixed> $payload */
    private function requiredInteger(array $payload, string $key): int
    {
        $value = Arr::get($payload, $key);

        if (! is_numeric($value)) {
            throw new InvalidArgumentException("Shipping field [{$key}] must be an integer.");
        }

        return (int) $value;
    }

    private function nullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            throw new InvalidArgumentException('Shipping numeric field is invalid.');
        }

        return (int) $value;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_scalar($value) && filled((string) $value) ? (string) $value : null;
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    private function requiredArray(array $payload, string $key): array
    {
        $value = Arr::get($payload, $key);

        if (! is_array($value) || $value === []) {
            throw new InvalidArgumentException("Shipping field [{$key}] is required.");
        }

        return $value;
    }

    /** @return array<string, mixed> */
    private function arrayValue(mixed $value, string $key): array
    {
        if (! is_array($value)) {
            throw new InvalidArgumentException("Shipping field [{$key}] must be an array.");
        }

        return $value;
    }
}
