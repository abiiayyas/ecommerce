<?php

namespace App\Actions\Supplier;

use App\Enums\OrderFulfillmentStatus;
use App\Enums\SupplierDispatchStatus;
use App\Jobs\BookSupplierOrderShipment;
use App\Models\Supplier\SupplierDispatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmSupplierDispatchAction
{
    public function handle(SupplierDispatch $dispatch): SupplierDispatch
    {
        $updated = DB::transaction(function () use ($dispatch): SupplierDispatch {
            $locked = SupplierDispatch::query()->with('orderShop.order')->lockForUpdate()->findOrFail($dispatch->getKey());

            if ($locked->status === SupplierDispatchStatus::Confirmed) {
                return $locked;
            }

            if ($locked->status === SupplierDispatchStatus::Failed) {
                throw ValidationException::withMessages(['dispatch' => 'Dispatch gagal dan perlu diperiksa sebelum dikonfirmasi ulang.']);
            }

            $locked->update([
                'status' => SupplierDispatchStatus::Confirmed,
                'confirmed_at' => now(),
            ]);
            $locked->orderShop->order->update(['fulfillment_status' => OrderFulfillmentStatus::Processing]);

            return $locked->refresh();
        });

        BookSupplierOrderShipment::dispatch($updated->order_shop_id)->afterCommit();

        return $updated;
    }
}
