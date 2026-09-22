<?php

namespace App\Models\Supplier;

use Database\Factories\Supplier\SupplierWarehouseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierWarehouse extends Model
{
    /** @use HasFactory<SupplierWarehouseFactory> */
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'name',
        'contact_name',
        'contact_phone',
        'address',
        'postal_code',
        'area_name',
        'latitude',
        'longitude',
        'primary_shipping_provider',
        'fallback_shipping_provider',
        'provider_area_ids',
        'provider_address_ids',
        'is_active',
    ];

    protected $attributes = [
        'is_active' => true,
        'primary_shipping_provider' => 'biteship',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'provider_area_ids' => 'array',
            'provider_address_ids' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(SupplierOffer::class);
    }
}
