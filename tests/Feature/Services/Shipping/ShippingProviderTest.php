<?php

use App\Data\Shipping\ShippingItemData;
use App\Data\Shipping\ShippingRateData;
use App\Data\Shipping\ShippingRateRequestData;
use App\Enums\ShippingProviderDriver;
use App\Exceptions\ShippingProviderException;
use App\Services\BiteshipService;
use App\Services\Shipping\BiteshipShippingProvider;
use App\Services\Shipping\MengantarShippingProvider;
use App\Services\Shipping\ShippingManager;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('normalizes Biteship rates', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.biteship.com/v1/rates/couriers' => Http::response([
            'success' => true,
            'pricing' => [[
                'courier_code' => 'jne',
                'courier_name' => 'JNE',
                'courier_service_code' => 'reg',
                'courier_service_name' => 'Regular',
                'price' => 15000,
                'duration' => '2 - 3 days',
                'available_for_cash_on_delivery' => true,
            ]],
        ]),
    ]);
    $provider = new BiteshipShippingProvider(new BiteshipService('biteship-key'));

    $rates = $provider->getRates(new ShippingRateRequestData(
        originAreaId: 'origin-1',
        destinationAreaId: 'destination-1',
        items: [shippingItem()],
        courierCodes: ['jne'],
    ));

    expect($rates)->toHaveCount(1);
    expect($rates[0])
        ->provider->toBe(ShippingProviderDriver::Biteship)
        ->courierCode->toBe('jne')
        ->serviceCode->toBe('reg')
        ->price->toBe(15000)
        ->supportsCod->toBeTrue();
    Http::assertSent(fn (Request $request): bool => $request['origin_area_id'] === 'origin-1'
        && $request['destination_area_id'] === 'destination-1'
        && $request['couriers'] === 'jne'
        && $request['items'][0]['weight'] === 500);
});

it('normalizes Mengantar rates and converts total weight to kilograms', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://app.mengantar.com/api/public/mengantar-key/order/estimate*' => Http::response([
            'data' => [
                'JNE' => [
                    'estimatedPrice' => 12000,
                    'estimatedDate' => '2-4 hari',
                    'unsupported_cod' => false,
                ],
                'SAP' => ['unsupported' => true],
            ],
        ]),
    ]);
    $provider = new MengantarShippingProvider('mengantar-key');

    $rates = $provider->getRates(new ShippingRateRequestData(
        originAreaId: 'origin-1',
        destinationAreaId: 'destination-1',
        items: [shippingItem(quantity: 3)],
        codAmount: 150000,
    ));

    expect($rates)->toHaveCount(1);
    expect($rates[0])
        ->provider->toBe(ShippingProviderDriver::Mengantar)
        ->courierCode->toBe('JNE')
        ->price->toBe(12000)
        ->duration->toBe('2-4 hari');
    Http::assertSent(fn (Request $request): bool => $request['weight'] === 2
        && $request['COD_AMOUNT'] === 150000
        && $request['courier'] === 'all');
});

it('normalizes a shipment created by Mengantar', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://app.mengantar.com/api/public/mengantar-key/order' => Http::response([
            'data' => [[
                '_id' => 'shipment-123',
                'tracking_id' => 'AWB-123',
                'status' => 'on_process',
                'courier' => 'JNE',
            ]],
        ]),
    ]);
    $manager = app(ShippingManager::class);
    app()->instance(MengantarShippingProvider::class, new MengantarShippingProvider('mengantar-key'));

    $shipment = $manager->createShipment('mengantar', [
        'reference' => 'ORDER-123',
        'origin' => ['external_id' => 'warehouse-1'],
        'destination' => [
            'contact_name' => 'Budi',
            'contact_phone' => '08123456789',
            'address' => 'Jakarta',
            'area_id' => 'destination-1',
        ],
        'courier_company' => 'JNE',
        'courier_service' => 'REG',
        'cod_amount' => 165000,
        'items' => [[
            'name' => 'Kaos',
            'value' => 75000,
            'weight' => 500,
            'quantity' => 2,
        ]],
    ]);

    expect($shipment)
        ->externalId->toBe('shipment-123')
        ->trackingNumber->toBe('AWB-123')
        ->status->toBe('in_transit');
    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && str_contains($request->body(), 'JNE')
        && str_contains($request->body(), 'warehouse-1'));
});

it('uses the fallback provider when the primary rate provider fails', function () {
    $primary = Mockery::mock(BiteshipShippingProvider::class);
    $primary->shouldReceive('getRates')
        ->once()
        ->andThrow(new ShippingProviderException(ShippingProviderDriver::Biteship, 'Unavailable'));
    $fallback = Mockery::mock(MengantarShippingProvider::class);
    $fallback->shouldReceive('getRates')
        ->once()
        ->andReturn([
            new ShippingRateData(
                provider: ShippingProviderDriver::Mengantar,
                courierCode: 'JNE',
                courierName: 'JNE',
                serviceCode: 'REG',
                serviceName: 'Reguler',
                price: 11000,
            ),
        ]);
    app()->instance(BiteshipShippingProvider::class, $primary);
    app()->instance(MengantarShippingProvider::class, $fallback);
    $manager = app(ShippingManager::class);

    $rates = $manager->rates('biteship', 'mengantar', shippingRatePayload());

    expect($rates)->toBe([[
        'provider' => 'mengantar',
        'courier_company' => 'JNE',
        'courier_name' => 'JNE',
        'courier_service' => 'REG',
        'description' => 'Reguler',
        'price' => 11000,
        'supports_cod' => false,
        'raw' => [],
    ]]);
});

it('throws a provider exception when Mengantar is not configured', function () {
    $provider = new MengantarShippingProvider('', 'https://app.mengantar.com');

    expect(fn () => $provider->searchAreas('Jakarta'))
        ->toThrow(ShippingProviderException::class, 'Mengantar is not configured.');
});

function shippingItem(int $quantity = 1): ShippingItemData
{
    return new ShippingItemData(
        name: 'Kaos',
        value: 75000,
        weightGrams: 500,
        quantity: $quantity,
    );
}

/** @return array<string, mixed> */
function shippingRatePayload(): array
{
    return [
        'origin_area_id' => 'origin-1',
        'destination_area_id' => 'destination-1',
        'items' => [[
            'name' => 'Kaos',
            'value' => 75000,
            'weight' => 500,
            'quantity' => 1,
        ]],
    ];
}
