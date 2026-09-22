<?php

namespace Database\Factories\Marketing;

use App\Models\Marketing\MarketingEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketingEvent>
 */
class MarketingEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => fake()->uuid(),
            'name' => 'ViewContent',
            'action_source' => 'website',
            'source_url' => fake()->url(),
            'customer_data' => [
                'email' => fake()->safeEmail(),
                'phone' => '6281234567890',
            ],
            'custom_data' => ['currency' => 'IDR'],
            'attribution' => ['utm_source' => 'meta'],
            'occurred_at' => now(),
        ];
    }
}
