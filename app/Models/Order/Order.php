<?php

namespace App\Models\Order;

use App\Enums\OrderFulfillmentStatus;
use App\Enums\SalesChannel;
use App\Models\Inventory\StockReservation;
use App\Models\Location\Location;
use App\Models\Marketing\LandingPage;
use App\Models\Payment\Payment;
use App\Models\User;
use Database\Factories\Order\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'location_id',
        'landing_page_id',
        'sales_channel',
        'payment_mode',
        'reference',
        'access_token',
        'ref_number',
        'guest_data',
        'total_checkout',
        'total_shipping',
        'application_fee',
        'insurance_fee',
        'payment_fee',
        'tax_total',
        'total',
        'status',
        'fulfillment_status',
    ];

    protected $attributes = [
        'sales_channel' => SalesChannel::Storefront->value,
        'fulfillment_status' => OrderFulfillmentStatus::Pending->value,
    ];

    protected function casts(): array
    {
        return [
            'guest_data' => 'array',
            'total_checkout' => 'decimal:2',
            'total_shipping' => 'decimal:2',
            'application_fee' => 'decimal:2',
            'insurance_fee' => 'decimal:2',
            'payment_fee' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'status' => 'boolean',
            'sales_channel' => SalesChannel::class,
            'fulfillment_status' => OrderFulfillmentStatus::class,
        ];
    }

    protected $hidden = [
        'access_token',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }

    public function orderShops(): HasMany
    {
        return $this->hasMany(OrderShop::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderShopItem::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function latestPayment(): MorphOne
    {
        return $this->morphOne(Payment::class, 'payable')->latestOfMany();
    }

    public function stockReservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }

    /** @return array<string, string> */
    public function guestRouteParameters(): array
    {
        return $this->user_id === null && filled($this->access_token)
            ? ['token' => $this->access_token]
            : [];
    }
}
