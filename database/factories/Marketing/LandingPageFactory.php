<?php

namespace Database\Factories\Marketing;

use App\Models\Marketing\LandingPage;
use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LandingPage>
 */
class LandingPageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'slug' => fake()->unique()->slug(),
            'headline' => fake()->sentence(6),
            'subheadline' => fake()->sentence(),
            'content' => [
                'benefits' => fake()->sentences(3),
                'description' => fake()->paragraph(),
                'faqs' => [],
                'testimonials' => [],
                'video_url' => null,
            ],
            'cta_text' => 'Pesan sekarang',
            'accent_color' => '#0c37b0',
            'online_payment_enabled' => true,
            'cod_enabled' => false,
            'is_active' => true,
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
            'published_at' => null,
        ]);
    }
}
