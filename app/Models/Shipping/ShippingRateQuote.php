<?php

namespace App\Models\Shipping;

use App\Models\Marketing\LandingPage;
use App\Models\Product\ProductFlat;
use Database\Factories\Shipping\ShippingRateQuoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ShippingRateQuote extends Model
{
    /** @use HasFactory<ShippingRateQuoteFactory> */
    use HasFactory;

    protected $fillable = [
        'public_id',
        'landing_page_id',
        'product_flat_id',
        'provider',
        'courier_company',
        'courier_service',
        'description',
        'price',
        'destination_area_id',
        'quantity',
        'payload_hash',
        'provider_payload',
        'expires_at',
        'consumed_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (ShippingRateQuote $quote): void {
            $quote->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'quantity' => 'integer',
            'provider_payload' => 'array',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }

    public function productFlat(): BelongsTo
    {
        return $this->belongsTo(ProductFlat::class);
    }

    public function isUsable(): bool
    {
        return $this->consumed_at === null && $this->expires_at->isFuture();
    }
}
