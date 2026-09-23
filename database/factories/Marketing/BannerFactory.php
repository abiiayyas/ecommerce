<?php

namespace Database\Factories\Marketing;

use App\Models\Marketing\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->sentence(),
            'button_text' => 'Belanja Sekarang',
            'button_url' => '/explore',
            'sort_order' => fake()->numberBetween(0, 20),
            'is_active' => true,
        ];
    }
}
