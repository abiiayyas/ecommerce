<?php

namespace App\Models\Marketing;

use App\Models\Order\Order;
use App\Models\Product\Product;
use Database\Factories\Marketing\LandingPageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class LandingPage extends Model implements HasMedia
{
    /** @use HasFactory<LandingPageFactory> */
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'product_id',
        'slug',
        'headline',
        'subheadline',
        'content',
        'cta_text',
        'accent_color',
        'online_payment_enabled',
        'cod_enabled',
        'meta_pixel_id',
        'is_active',
        'published_at',
    ];

    protected $attributes = [
        'cta_text' => 'Pesan sekarang',
        'accent_color' => '#ca4a2c',
        'online_payment_enabled' => true,
        'cod_enabled' => false,
        'is_active' => false,
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'online_payment_enabled' => 'boolean',
            'cod_enabled' => 'boolean',
            'is_active' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function dailyStats(): HasMany
    {
        return $this->hasMany(LandingPageDailyStat::class);
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('hero')->singleFile()->withResponsiveImages();
        $this->addMediaCollection('gallery')->withResponsiveImages();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('hero')
            ->fit(Fit::Max, 1280, 1280)
            ->format('webp')
            ->quality(82)
            ->withResponsiveImages();

        $this->addMediaConversion('card')
            ->fit(Fit::Crop, 640, 640)
            ->format('webp')
            ->quality(80)
            ->performOnCollections('gallery');
    }
}
