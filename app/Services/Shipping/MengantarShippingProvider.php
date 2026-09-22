<?php

namespace App\Services\Shipping;

use App\Contracts\Shipping\ShippingProvider;
use App\Data\Shipping\CreateShipmentData;
use App\Data\Shipping\ShipmentData;
use App\Data\Shipping\ShippingAreaData;
use App\Data\Shipping\ShippingItemData;
use App\Data\Shipping\ShippingRateData;
use App\Data\Shipping\ShippingRateRequestData;
use App\Enums\ShippingProviderDriver;
use App\Exceptions\ShippingProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class MengantarShippingProvider implements ShippingProvider
{
    private readonly string $apiKey;

    private readonly string $baseUrl;

    public function __construct(?string $apiKey = null, ?string $baseUrl = null)
    {
        $configuredApiKey = $apiKey ?? config('services.mengantar.api_key');
        $configuredBaseUrl = $baseUrl ?? config('services.mengantar.base_url', 'https://app.mengantar.com');

        $this->apiKey = is_string($configuredApiKey) ? $configuredApiKey : '';
        $this->baseUrl = is_string($configuredBaseUrl) ? rtrim($configuredBaseUrl, '/') : 'https://app.mengantar.com';
    }

    public function driver(): ShippingProviderDriver
    {
        return ShippingProviderDriver::Mengantar;
    }

    public function searchAreas(string $query): array
    {
        $response = $this->request('GET', 'address/search', ['keyword' => $query]);
        $areas = Arr::get($response, 'data', []);

        if (! is_array($areas)) {
            return [];
        }

        return collect($areas)
            ->filter(fn (mixed $area): bool => is_array($area) && filled(Arr::get($area, '_id')))
            ->map(function (array $area): ShippingAreaData {
                $subdistrict = (string) Arr::get($area, 'SUBDISTRICT_NAME', '');
                $district = (string) Arr::get($area, 'DISTRICT_NAME', '');

                return new ShippingAreaData(
                    provider: $this->driver(),
                    id: (string) Arr::get($area, '_id'),
                    name: collect([$subdistrict, $district])->filter()->implode(', '),
                    province: $this->nullableString(Arr::get($area, 'PROVINCE_NAME')),
                    city: $this->nullableString(Arr::get($area, 'CITY_NAME')),
                    postalCode: $this->nullableString(Arr::get($area, 'ZIP_CODE')),
                    raw: $area,
                );
            })
            ->values()
            ->all();
    }

    public function getRates(ShippingRateRequestData $request): array
    {
        $response = $this->request('GET', 'order/estimate', [
            'origin_id' => $request->originAreaId,
            'destination_id' => $request->destinationAreaId,
            'courier' => $request->courierCodes === [] ? 'all' : implode(',', $request->courierCodes),
            'weight' => $this->totalWeightKilograms($request->items),
            'COD_AMOUNT' => $request->codAmount ?? $this->totalValue($request->items),
        ]);
        $pricing = Arr::get($response, 'data', []);

        if (! is_array($pricing)) {
            return [];
        }

        $rates = [];

        foreach ($pricing as $courierCode => $rate) {
            if (! is_array($rate) || (bool) Arr::get($rate, 'unsupported', false)) {
                continue;
            }

            $code = is_string($courierCode) ? $courierCode : (string) Arr::get($rate, 'courier', '');

            if (blank($code)) {
                continue;
            }

            $rates[] = new ShippingRateData(
                provider: $this->driver(),
                courierCode: $code,
                courierName: Str::upper($code),
                serviceCode: 'REG',
                serviceName: 'Reguler',
                price: (int) Arr::get($rate, 'estimatedPrice', Arr::get($rate, 'price', 0)),
                duration: $this->nullableString(Arr::get($rate, 'estimatedDate')),
                supportsCod: ! (bool) Arr::get($rate, 'unsupported_cod', false),
                raw: $rate,
            );
        }

        return $rates;
    }

    public function createShipment(CreateShipmentData $shipment): ShipmentData
    {
        $originAddressId = $this->nullableString(Arr::get($shipment->origin, 'external_id'));

        if ($originAddressId === null) {
            throw new ShippingProviderException($this->driver(), 'Mengantar origin address ID is required.');
        }

        $order = [
            'customerAddress' => Arr::get($shipment->destination, 'address'),
            'customerName' => Arr::get($shipment->destination, 'contact_name'),
            'customerAddressDataId' => Arr::get($shipment->destination, 'area_id'),
            'customerPhone' => Arr::get($shipment->destination, 'contact_phone'),
            'parcelContent' => collect($shipment->items)->pluck('name')->implode(', '),
            'weight' => $this->totalWeightKilograms($shipment->items),
            'quantity' => collect($shipment->items)->sum('quantity'),
            'reference' => $shipment->reference,
        ];

        if ($shipment->codAmount !== null) {
            $order['COD'] = $shipment->codAmount;
        } else {
            $order['goodsValue'] = $this->totalValue($shipment->items);
        }

        $response = $this->request('POST', 'order', [
            'courier' => $shipment->courierCode,
            'pickup' => json_encode(['type' => 'dropOff', 'address_id' => $originAddressId], JSON_THROW_ON_ERROR),
            'orders' => json_encode([$order], JSON_THROW_ON_ERROR),
        ]);
        $created = Arr::get($response, 'data.0');

        if (! is_array($created)) {
            throw new ShippingProviderException($this->driver(), 'Mengantar returned an invalid shipment response.');
        }

        $externalId = $this->nullableString(Arr::get($created, '_id', Arr::get($created, 'id')));
        $trackingNumber = $this->nullableString(Arr::get($created, 'tracking_id', Arr::get($created, 'trackingNumber')));

        if ($externalId === null) {
            $externalId = $trackingNumber;
        }

        if ($externalId === null) {
            throw new ShippingProviderException($this->driver(), 'Mengantar returned a shipment without an external ID.');
        }

        return new ShipmentData(
            provider: $this->driver(),
            externalId: $externalId,
            trackingNumber: $trackingNumber,
            status: $this->normalizeStatus((string) Arr::get($created, 'status', 'pending')),
            courierCode: $this->nullableString(Arr::get($created, 'courier', $shipment->courierCode)),
            courierService: $shipment->courierService,
            raw: $created,
        );
    }

    public function trackShipment(string $externalId, ?string $trackingNumber = null, ?string $courierCode = null): ShipmentData
    {
        $response = $this->request('GET', 'order', ['tracking_id' => $trackingNumber ?? $externalId]);
        $shipment = Arr::get($response, 'data.0');

        if (! is_array($shipment)) {
            throw new ShippingProviderException($this->driver(), 'Mengantar returned an invalid tracking response.');
        }

        return new ShipmentData(
            provider: $this->driver(),
            externalId: $externalId,
            trackingNumber: $this->nullableString(Arr::get($shipment, 'tracking_id', $trackingNumber)),
            status: $this->normalizeStatus((string) Arr::get($shipment, 'status', 'pending')),
            courierCode: $this->nullableString(Arr::get($shipment, 'courier', $courierCode)),
            courierService: $this->nullableString(Arr::get($shipment, 'service')),
            raw: $shipment,
        );
    }

    /** @param list<ShippingItemData> $items */
    private function totalWeightKilograms(array $items): int
    {
        $weightGrams = collect($items)->sum(fn (ShippingItemData $item): int => $item->weightGrams * $item->quantity);

        return max(1, (int) ceil($weightGrams / 1000));
    }

    /** @param list<ShippingItemData> $items */
    private function totalValue(array $items): int
    {
        return (int) collect($items)->sum(fn (ShippingItemData $item): int => $item->value * $item->quantity);
    }

    /** @param array<string, mixed> $data */
    private function request(string $method, string $path, array $data): array
    {
        if (blank($this->apiKey)) {
            throw new ShippingProviderException($this->driver(), 'Mengantar is not configured.');
        }

        $url = $this->baseUrl.'/api/public/'.rawurlencode($this->apiKey).'/'.ltrim($path, '/');

        try {
            $request = $this->requestClient();
            $response = Str::upper($method) === 'GET'
                ? $request->get($url, $data)
                : $request->asMultipart()->post($url, $data);
        } catch (ConnectionException $exception) {
            throw new ShippingProviderException($this->driver(), 'Mengantar connection failed.', $exception);
        } catch (Throwable $exception) {
            throw new ShippingProviderException($this->driver(), 'Mengantar request failed.', $exception);
        }

        if ($response->failed()) {
            throw new ShippingProviderException($this->driver(), "Mengantar request failed with status {$response->status()}.");
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new ShippingProviderException($this->driver(), 'Mengantar returned an invalid response.');
        }

        return $payload;
    }

    private function requestClient(): PendingRequest
    {
        return Http::acceptJson()
            ->connectTimeout(5)
            ->timeout(15)
            ->retry([200, 500, 1000], throw: false);
    }

    private function normalizeStatus(string $status): string
    {
        return match (Str::lower($status)) {
            'confirmed', 'allocated', 'ready_to_pickup' => 'confirmed',
            'picking_up', 'picked', 'dropping_off', 'in_transit', 'on_process' => 'in_transit',
            'delivered', 'completed' => 'delivered',
            'cancelled', 'canceled', 'returned' => 'cancelled',
            'failed', 'rejected' => 'failed',
            default => 'pending',
        };
    }

    private function nullableString(mixed $value): ?string
    {
        return is_scalar($value) && filled((string) $value) ? (string) $value : null;
    }
}
