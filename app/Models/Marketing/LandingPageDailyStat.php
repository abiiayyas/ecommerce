<?php

namespace App\Models\Marketing;

use Database\Factories\Marketing\LandingPageDailyStatFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingPageDailyStat extends Model
{
    /** @use HasFactory<LandingPageDailyStatFactory> */
    use HasFactory;

    protected $fillable = [
        'landing_page_id',
        'campaign_id',
        'date',
        'page_views',
        'checkouts',
        'orders',
        'paid_orders',
        'revenue',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'page_views' => 'integer',
            'checkouts' => 'integer',
            'orders' => 'integer',
            'paid_orders' => 'integer',
            'revenue' => 'decimal:2',
        ];
    }

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }
}
