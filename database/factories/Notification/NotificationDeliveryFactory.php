<?php

namespace Database\Factories\Notification;

use App\Models\Notification\NotificationDelivery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationDelivery>
 */
class NotificationDeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'channel' => 'whatsapp',
            'provider' => 'fonnte',
            'message_type' => 'order_confirmation',
            'deduplication_key' => hash('sha256', fake()->uuid()),
            'recipient_hash' => hash('sha256', '6281234567890'),
            'recipient' => '6281234567890',
            'message' => fake()->sentence(),
            'context' => [],
            'status' => NotificationDelivery::STATUS_PENDING,
            'attempts' => 0,
        ];
    }
}
