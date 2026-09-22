<?php

namespace App\Models\Marketing;

use Database\Factories\Marketing\MarketingEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingEvent extends Model
{
    /** @use HasFactory<MarketingEventFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'action_source',
        'source_url',
        'customer_data',
        'custom_data',
        'attribution',
        'occurred_at',
    ];

    protected $attributes = [
        'action_source' => 'website',
    ];

    protected $hidden = [
        'customer_data',
    ];

    protected function casts(): array
    {
        return [
            'customer_data' => 'encrypted:array',
            'custom_data' => 'array',
            'attribution' => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(MarketingEventDelivery::class);
    }
}
