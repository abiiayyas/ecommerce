<?php

namespace App\Models\Marketing;

use Database\Factories\Marketing\MarketingEventDeliveryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingEventDelivery extends Model
{
    /** @use HasFactory<MarketingEventDeliveryFactory> */
    use HasFactory;

    public const STATUS_FAILED = 'failed';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    protected $fillable = [
        'marketing_event_id',
        'provider',
        'status',
        'attempts',
        'external_id',
        'response',
        'last_error',
        'delivered_at',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'attempts' => 0,
    ];

    protected $hidden = [
        'response',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'response' => 'encrypted:array',
            'last_error' => 'encrypted',
            'delivered_at' => 'immutable_datetime',
        ];
    }

    public function marketingEvent(): BelongsTo
    {
        return $this->belongsTo(MarketingEvent::class);
    }
}
