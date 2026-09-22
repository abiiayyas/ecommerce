<?php

namespace App\Models\Product;

use App\Enums\FulfillmentType;
use App\Models\Inventory\StockReservation;
use App\Models\Shop\Shop;
use App\Models\Supplier\SupplierOffer;
use Database\Factories\Product\ProductFlatFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ProductFlat extends Model implements HasMedia
{
    /** @use HasFactory<ProductFlatFactory> */
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'shop_id',
        'product_id',
        'name',
        'description',
        'price',
        'weight',
        'length',
        'width',
        'height',
        'stock',
        'rating',
        'total_reviews',
        'total_sales',
        'is_unlimited_stock',
        'status',
        'fulfillment_type',
        'active_supplier_offer_id',
    ];

    protected $attributes = [
        'fulfillment_type' => FulfillmentType::OwnedStock->value,
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'weight' => 'decimal:2',
            'length' => 'decimal:2',
            'width' => 'decimal:2',
            'height' => 'decimal:2',
            'stock' => 'integer',
            'rating' => 'decimal:2',
            'total_reviews' => 'integer',
            'total_sales' => 'integer',
            'is_unlimited_stock' => 'boolean',
            'status' => 'boolean',
            'fulfillment_type' => FulfillmentType::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ProductFlat $productFlat): void {
            if ($productFlat->fulfillment_type === FulfillmentType::OwnedStock && $productFlat->active_supplier_offer_id !== null) {
                throw ValidationException::withMessages([
                    'active_supplier_offer_id' => 'Stok milik sendiri tidak boleh memiliki penawaran supplier aktif.',
                ]);
            }

            if ($productFlat->fulfillment_type === FulfillmentType::SupplierDropship && $productFlat->active_supplier_offer_id === null) {
                throw ValidationException::withMessages([
                    'active_supplier_offer_id' => 'Fulfillment dropship wajib memiliki satu penawaran supplier aktif.',
                ]);
            }
        });
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function supplierOffers(): HasMany
    {
        return $this->hasMany(SupplierOffer::class);
    }

    public function activeSupplierOffer(): BelongsTo
    {
        return $this->belongsTo(SupplierOffer::class, 'active_supplier_offer_id');
    }

    public function stockReservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }
}
