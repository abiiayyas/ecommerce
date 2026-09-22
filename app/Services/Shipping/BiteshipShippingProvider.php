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
use App\Services\BiteshipService;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

class BiteshipShippingProvider implements ShippingProvider
{
    public function __construct(
        private readonly BiteshipService $biteship,
    ) {}

    public function driver(): ShippingProviderDriver
    {
        return ShippingProviderDriver::Biteship;
    }

    public function searchAreas(string $query): array
    {
        $response = $this->call(fn (): array => $this->biteship->getMapsAreas(['input' => $query]));
        $areas = Arr::get($response, 'areas', Arr::get($response, 'data', []));

        if (! is_array($areas)) {
            return [];
        }

        return collect($areas)
            ->filter(fn (mixed $area): bool => is_array($area) && filled(Arr::get($area, 'id')))
            ->map(fn (array $area): ShippingAreaData => new ShippingAreaData(
                provider: $this->driver(),
                id: (string) Arr::get($area, 'id'),
                name: (string) Arr::get($area, 'name', Arr::get($area, 'administrative_division_level_3_name', '')),
                province: $this->nullableString(Arr::get($area, 'administrative_division_level_1_name')),
                city: $this->nullableString(Arr::get($area, 'administrative_division_level_2_name')),
                postalCode: $this->nullableString(Arr::get($area, 'postal_code')),
                raw: $area,
            ))
            ->values()
            ->all();
    }

    public function getRates(ShippingRateRequestData $request): array
    {
        $response = $this->call(fn (): array => $this->biteship->getRates($this->ratePayload($request)));
        $pricing = Arr::get($response, 'pricing', []);

        if (! is_array($pricing)) {
            return [];
        }

        return collect($pricing)
            ->filter(fn (mixed $rate): bool => is_array($rate) && filled(Arr::get($rate, 'courier_code')))
            ->map(fn (array $rate): ShippingRateData => new ShippingRateData(
                provider: $this->driver(),
                courierCode: (string) Arr::get($rate, 'courier_code'),
                courierName: (string) Arr::get($rate, 'courier_name', Str::upper((string) Arr::get($rate, 'courier_code'))),
                serviceCode: (string) Arr::get($rate, 'courier_service_code', Arr::get($rate, 'courier_service_name', '')),
                serviceName: (string) Arr::get($rate, 'courier_service_name', Arr::get($rate, 'courier_service_code', '')),
                price: (int) Arr::get($rate, 'price', 0),
                duration: $this->nullableString(Arr::get($rate, 'duration')),
                supportsCod: (bool) Arr::get($rate, 'available_for_cash_on_delivery', false),
                raw: $rate,
            ))
            ->values()
            ->all();
    }

    public function createShipment(CreateShipmentData $shipment): ShipmentData
    {
        $response = $this->call(fn (): array => $this->biteship->createOrder($this->shipmentPayload($shipment)));
        $externalId = $this->nullableString(Arr::get($response, 'id'));

        if ($externalId === null) {
            throw new ShippingProviderException($this->driver(), 'Biteship returned a shipment without an external ID.');
        }

        return new ShipmentData(
            provider: $this->driver(),
            externalId: $externalId,
            trackingNumber: $this->nullableString(Arr::get($response, 'courier.waybill_id')),
            status: $this->normalizeStatus((string) Arr::get($response, 'status', 'pending')),
            courierCode: $this->nullableString(Arr::get($response, 'courier.company', $shipment->courierCode)),
            courierService: $this->nullableString(Arr::get($response, 'courier.type', $shipment->courierService)),
            raw: $response,
        );
    }

    public function trackShipment(string $externalId, ?string $trackingNumber = null, ?string $courierCode = null): ShipmentData
    {
        $response = $this->call(fn (): array => $this->biteship->getOrder($externalId));

        return new ShipmentData(
            provider: $this->driver(),
            externalId: $externalId,
            trackingNumber: $this->nullableString(Arr::get($response, 'courier.waybill_id', $trackingNumber)),
            status: $this->normalizeStatus((string) Arr::get($response, 'status', 'pending')),
            courierCode: $this->nullableString(Arr::get($response, 'courier.company', $courierCode)),
            courierService: $this->nullableString(Arr::get($response, 'courier.type')),
            raw: $response,
        );
    }

    /** @return array<string, mixed> */
    private function ratePayload(ShippingRateRequestData $request): array
    {
        $payload = [
            'origin_area_id' => $request->originAreaId,
            'destination_area_id' => $request->destinationAreaId,
            'items' => array_map(fn (ShippingItemData $item): array => $this->itemPayload($item), $request->items),
        ];

        if ($request->courierCodes !== []) {
            $payload['couriers'] = implode(',', $request->courierCodes);
        }

        return $payload;
    }

    /** @return array<string, mixed> */
    private function shipmentPayload(CreateShipmentData $shipment): array
    {
        return [
            'origin_contact_name' => Arr::get($shipment->origin, 'contact_name'),
            'origin_contact_phone' => Arr::get($shipment->origin, 'contact_phone'),
            'origin_address' => Arr::get($shipment->origin, 'address'),
            'origin_area_id' => Arr::get($shipment->origin, 'area_id'),
            'destination_contact_name' => Arr::get($shipment->destination, 'contact_name'),
            'destination_contact_phone' => Arr::get($shipment->destination, 'contact_phone'),
            'destination_contact_email' => Arr::get($shipment->destination, 'contact_email'),
            'destination_address' => Arr::get($shipment->destination, 'address'),
            'destination_area_id' => Arr::get($shipment->destination, 'area_id'),
            'destination_postal_code' => Arr::get($shipment->destination, 'postal_code'),
            'courier_company' => $shipment->courierCode,
            'courier_type' => $shipment->courierService,
            'delivery_type' => 'now',
            'order_note' => $shipment->reference,
            'metadata' => $shipment->metadata,
            'items' => array_map(fn (ShippingItemData $item): array => $this->itemPayload($item), $shipment->items),
        ];
    }

    /** @return array{name: string, description: string, value: int, weight: int, quantity: int} */
    private function itemPayload(ShippingItemData $item): array
    {
        return [
            'name' => $item->name,
            'description' => $item->description ?? $item->name,
            'value' => $item->value,
            'weight' => $item->weightGrams,
            'quantity' => $item->quantity,
        ];
    }

    private function normalizeStatus(string $status): string
    {
        return match (Str::lower($status)) {
            'confirmed', 'allocated' => 'confirmed',
            'picking_up', 'picked', 'dropping_off', 'in_transit' => 'in_transit',
            'delivered' => 'delivered',
            'cancelled', 'canceled' => 'cancelled',
            'rejected', 'courier_not_found' => 'failed',
            default => 'pending',
        };
    }

    private function nullableString(mixed $value): ?string
    {
        return is_scalar($value) && filled((string) $value) ? (string) $value : null;
    }

    /** @return array<string, mixed> */
    private function call(callable $callback): array
    {
        try {
            return $callback();
        } catch (ShippingProviderException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new ShippingProviderException($this->driver(), 'Biteship request failed.', $exception);
        }
    }
}
