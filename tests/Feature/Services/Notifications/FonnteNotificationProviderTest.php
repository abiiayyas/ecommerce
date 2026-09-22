<?php

use App\Models\Notification\NotificationDelivery;
use App\Services\Notifications\FonnteNotificationProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::preventStrayRequests();

    config()->set([
        'notifications.channels.whatsapp.country_code' => '62',
        'notifications.providers.fonnte.token' => 'fonnte-secret',
        'notifications.providers.fonnte.base_url' => 'https://api.fonnte.com/send',
        'notifications.providers.fonnte.connect_timeout' => 3,
        'notifications.providers.fonnte.timeout' => 10,
    ]);
});

it('sends a form-encoded WhatsApp message through Fonnte', function () {
    Http::fake([
        'https://api.fonnte.com/send' => Http::response([
            'status' => true,
            'id' => ['message-1001'],
        ]),
    ]);
    $delivery = NotificationDelivery::factory()->create([
        'recipient' => '6281234567890',
        'message' => 'Pesanan ORDER-1001 sudah dikirim.',
    ]);

    $result = app(FonnteNotificationProvider::class)->send($delivery);

    expect($result->externalId)->toBe('message-1001');
    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request->url() === 'https://api.fonnte.com/send'
        && $request->hasHeader('Authorization', 'fonnte-secret')
        && $request->data() === [
            'target' => '6281234567890',
            'message' => 'Pesanan ORDER-1001 sudah dikirim.',
            'countryCode' => '62',
        ]);
    Http::assertSentCount(1);
});

it('rejects unsuccessful Fonnte responses even when HTTP succeeds', function () {
    Http::fake([
        'https://api.fonnte.com/send' => Http::response([
            'status' => false,
            'reason' => 'device disconnected',
        ]),
    ]);
    $delivery = NotificationDelivery::factory()->create();

    expect(fn () => app(FonnteNotificationProvider::class)->send($delivery))
        ->toThrow(UnexpectedValueException::class, 'Fonnte rejected the notification.');

    Http::assertSentCount(1);
});
