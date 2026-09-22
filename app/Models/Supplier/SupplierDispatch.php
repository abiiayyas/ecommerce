<?php

namespace App\Models\Supplier;

use App\Enums\SupplierDispatchStatus;
use App\Models\Order\OrderShop;
use Database\Factories\Supplier\SupplierDispatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierDispatch extends Model
{
    /** @use HasFactory<SupplierDispatchFactory> */
    use HasFactory;

    protected $fillable = [
        'order_shop_id',
        'supplier_id',
        'supplier_warehouse_id',
        'status',
        'order_snapshot',
        'notes',
        'sent_at',
        'confirmed_at',
    ];

    protected $attributes = [
        'status' => SupplierDispatchStatus::Pending->value,
    ];

    protected function casts(): array
    {
        return [
            'status' => SupplierDispatchStatus::class,
            'order_snapshot' => 'array',
            'sent_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function orderShop(): BelongsTo
    {
        return $this->belongsTo(OrderShop::class);
    }
}
