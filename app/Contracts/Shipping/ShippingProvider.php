<?php

namespace App\Contracts\Shipping;

use App\Data\Shipping\CreateShipmentData;
use App\Data\Shipping\ShipmentData;
use App\Data\Shipping\ShippingAreaData;
use App\Data\Shipping\ShippingRateData;
use App\Data\Shipping\ShippingRateRequestData;
use App\Enums\ShippingProviderDriver;

interface ShippingProvider
{
    public function driver(): ShippingProviderDriver;

    /** @return list<ShippingAreaData> */
    public function searchAreas(string $query): array;

    /** @return list<ShippingRateData> */
    public function getRates(ShippingRateRequestData $request): array;

    public function createShipment(CreateShipmentData $shipment): ShipmentData;

    public function trackShipment(string $externalId, ?string $trackingNumber = null, ?string $courierCode = null): ShipmentData;
}
