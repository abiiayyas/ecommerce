<?php

namespace App\Jobs;

use App\Models\Order\OrderShopShipment;
use App\Services\Shipping\ShippingManager;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class SyncOrderShopShipment implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $shipmentId) {}

    public function uniqueId(): string
    {
        return (string) $this->shipmentId;
    }

    public function handle(ShippingManager $shipping): void
    {
        $shipment = OrderShopShipment::query()->findOrFail($this->shipmentId);
        if (blank($shipment->external_id)) {
            return;
        }

        $tracked = $shipping->trackShipment(
            (string) $shipment->provider,
            (string) $shipment->external_id,
            $shipment->courier_tracking_id,
            $shipment->courier_company,
        );

        DB::transaction(function () use ($shipment, $tracked): void {
            $history = $shipment->status_history ?? [];
            $history[] = ['status' => $tracked->status, 'at' => now()->toIso8601String()];
            $shipment->update([
                'status' => $tracked->status,
                'courier_tracking_id' => $tracked->trackingNumber ?? $shipment->courier_tracking_id,
                'courier_waybill_id' => $tracked->trackingNumber ?? $shipment->courier_waybill_id,
                'provider_payload' => $tracked->raw,
                'status_history' => $history,
                'tracked_at' => now(),
            ]);
        });
    }
}
