<?php

namespace App\Models\Supplier;

use App\Models\Product\ProductFlat;
use Database\Factories\Supplier\SupplierOfferFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierOffer extends Model
{
    /** @use HasFactory<SupplierOfferFactory> */
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'supplier_warehouse_id',
        'product_flat_id',
        'supplier_sku',
        'cost_price',
        'available_stock',
        'is_available',
        'is_active',
        'notes',
    ];

    protected $attributes = [
        'is_available' => true,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'available_stock' => 'integer',
            'is_available' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(SupplierWarehouse::class, 'supplier_warehouse_id');
    }

    public function productFlat(): BelongsTo
    {
        return $this->belongsTo(ProductFlat::class);
    }
}
