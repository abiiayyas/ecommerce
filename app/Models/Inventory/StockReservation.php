<?php

namespace App\Models\Inventory;

use App\Enums\StockReservationStatus;
use App\Models\Order\Order;
use App\Models\Product\ProductFlat;
use Database\Factories\Inventory\StockReservationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockReservation extends Model
{
    /** @use HasFactory<StockReservationFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_flat_id',
        'supplier_offer_id',
        'quantity',
        'status',
        'expires_at',
        'released_at',
        'committed_at',
    ];

    protected $attributes = [
        'status' => StockReservationStatus::Active->value,
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'status' => StockReservationStatus::class,
            'expires_at' => 'datetime',
            'released_at' => 'datetime',
            'committed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function productFlat(): BelongsTo
    {
        return $this->belongsTo(ProductFlat::class);
    }
}
