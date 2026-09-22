<?php

namespace Database\Factories\Marketing;

use App\Models\Marketing\MarketingEvent;
use App\Models\Marketing\MarketingEventDelivery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketingEventDelivery>
 */
class MarketingEventDeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'marketing_event_id' => MarketingEvent::factory(),
            'provider' => 'meta',
            'status' => MarketingEventDelivery::STATUS_PENDING,
            'attempts' => 0,
        ];
    }
}
