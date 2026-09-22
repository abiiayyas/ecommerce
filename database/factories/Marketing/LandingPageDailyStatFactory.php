<?php

namespace Database\Factories\Marketing;

use App\Models\Marketing\LandingPage;
use App\Models\Marketing\LandingPageDailyStat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LandingPageDailyStat>
 */
class LandingPageDailyStatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'landing_page_id' => LandingPage::factory(),
            'campaign_id' => 0,
            'date' => today(),
            'page_views' => 1,
            'checkouts' => 0,
            'orders' => 0,
            'paid_orders' => 0,
            'revenue' => 0,
        ];
    }
}
