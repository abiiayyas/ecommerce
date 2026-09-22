<?php

namespace App\Models\Order;

use Database\Factories\Order\OrderShopShipmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderShopShipment extends Model
{
    /** @use HasFactory<OrderShopShipmentFactory> */
    use HasFactory;

    protected $fillable = [
        'order_shop_id',
        'provider',
        'external_id',
        'event',
        'provider_event_key',
        'courier_tracking_id',
        'courier_waybill_id',
        'courier_name',
        'courier_company',
        'courier_type',
        'courier_driver_name',
        'courier_driver_phone',
        'courier_driver_photo_url',
        'courier_driver_plate_number',
        'courier_link',
        'status',
        'provider_payload',
        'status_history',
        'last_error',
        'booked_at',
        'tracked_at',
    ];

    protected function casts(): array
    {
        return [
            'provider_payload' => 'array',
            'status_history' => 'array',
            'booked_at' => 'datetime',
            'tracked_at' => 'datetime',
        ];
    }

    public function orderShop()
    {
        return $this->belongsTo(OrderShop::class);
    }
}
