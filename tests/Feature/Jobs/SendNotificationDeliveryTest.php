<?php

use App\Jobs\SendNotificationDelivery;
use App\Models\Notification\NotificationDelivery;
use App\Services\Notifications\NotificationProviderManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('marks a Fonnte notification delivery as sent after the provider accepts it', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.fonnte.com/send' => Http::response([
            'status' => true,
            'id' => ['message-1001'],
        ]),
    ]);
    config()->set([
        'notifications.channels.whatsapp.country_code' => '62',
        'notifications.providers.fonnte.token' => 'fonnte-secret',
        'notifications.providers.fonnte.base_url' => 'https://api.fonnte.com/send',
        'notifications.providers.fonnte.connect_timeout' => 3,
        'notifications.providers.fonnte.timeout' => 10,
    ]);
    $delivery = NotificationDelivery::factory()->create();

    (new SendNotificationDelivery($delivery))->handle(app(NotificationProviderManager::class));

    $delivery->refresh();
    expect($delivery->status)->toBe(NotificationDelivery::STATUS_SENT)
        ->and($delivery->attempts)->toBe(1)
        ->and($delivery->external_id)->toBe('message-1001')
        ->and($delivery->sent_at)->not->toBeNull();
});
