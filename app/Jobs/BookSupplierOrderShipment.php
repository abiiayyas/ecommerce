<?php

namespace App\Jobs;

use App\Models\Order\OrderShop;
use App\Models\Supplier\SupplierWarehouse;
use App\Services\Shipping\ShippingManager;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BookSupplierOrderShipment implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $orderShopId) {}

    public function uniqueId(): string
    {
        return (string) $this->orderShopId;
    }

    public function handle(ShippingManager $shipping): void
    {
        $orderShop = OrderShop::query()->with([
            'order', 'items', 'latestShipment',
            'items.productFlat.activeSupplierOffer.warehouse',
        ])->findOrFail($this->orderShopId);
        $item = $orderShop->items->first();
        $warehouse = $item?->productFlat?->activeSupplierOffer?->warehouse;
        $shippingData = $orderShop->shipping_data ?? [];
        $provider = (string) ($shippingData['provider'] ?? 'biteship');

        if (! $warehouse instanceof SupplierWarehouse) {
            throw new RuntimeException('Gudang supplier tidak ditemukan untuk shipment.');
        }

        $existing = $orderShop->latestShipment;
        if ($existing?->external_id) {
            return;
        }

        $shipment = $shipping->createShipment($provider, [
            'reference' => $orderShop->order->reference.'-'.$orderShop->getKey(),
            'origin' => [
                'contact_name' => $warehouse->contact_name,
                'contact_phone' => $warehouse->contact_phone,
                'address' => $warehouse->address,
                'postal_code' => $warehouse->postal_code,
                'area_id' => Arr::get($warehouse->provider_area_ids ?? [], $provider),
                'provider_address_id' => Arr::get($warehouse->provider_address_ids ?? [], $provider),
                'external_id' => Arr::get($warehouse->provider_address_ids ?? [], $provider),
            ],
            'destination' => $this->destination($orderShop),
            'courier_company' => (string) ($shippingData['company'] ?? ''),
            'courier_service' => (string) ($shippingData['type'] ?? ''),
            'cod_amount' => $orderShop->order->payment_mode === 'cod' ? (int) round((float) $orderShop->order->total) : null,
            'items' => $orderShop->items->map(fn ($orderItem): array => [
                'name' => $orderItem->product_data['name'] ?? 'Product',
                'description' => $orderItem->product_data['description'] ?? null,
                'value' => (int) round((float) $orderItem->price),
                'weight' => (int) ($orderItem->product_data['weight'] ?? 1),
                'quantity' => (int) $orderItem->quantity,
            ])->all(),
            'metadata' => ['order_shop_id' => $orderShop->getKey()],
        ]);

        DB::transaction(function () use ($orderShop, $shipment, $provider): void {
            $locked = OrderShop::query()->lockForUpdate()->findOrFail($orderShop->getKey());
            $existing = $locked->shipments()->whereNotNull('external_id')->first();
            if ($existing) {
                return;
            }

            $locked->shipments()->create([
                'event' => 'create_order',
                'provider' => $provider,
                'external_id' => $shipment->externalId,
                'courier_tracking_id' => $shipment->trackingNumber,
                'courier_waybill_id' => $shipment->trackingNumber,
                'courier_company' => $shipment->courierCode,
                'courier_type' => $shipment->courierService,
                'status' => $shipment->status,
                'provider_payload' => $shipment->raw,
                'status_history' => [['status' => $shipment->status, 'at' => now()->toIso8601String()]],
                'booked_at' => now(),
            ]);
            $locked->update(['waybill_number' => $shipment->trackingNumber]);
        });
    }

    /** @return array<string, mixed> */
    private function destination(OrderShop $orderShop): array
    {
        $guest = $orderShop->order->guest_data ?? [];

        return [
            'contact_name' => $guest['contact_name'] ?? null,
            'contact_phone' => $guest['contact_phone'] ?? null,
            'contact_email' => $guest['contact_email'] ?? null,
            'address' => $guest['address'] ?? null,
            'postal_code' => $guest['postal_code'] ?? null,
            'area_id' => $guest['shipping_area_id'] ?? $guest['biteship_area_id'] ?? null,
            'latitude' => $guest['latitude'] ?? null,
            'longitude' => $guest['longitude'] ?? null,
        ];
    }
}
