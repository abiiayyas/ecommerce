<?php

use App\Jobs\SendNotificationDelivery;
use App\Models\Notification\NotificationDelivery;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('stores and queues a WhatsApp notification once for an idempotency key', function () {
    Queue::fake([SendNotificationDelivery::class]);
    config()->set([
        'notifications.channels.whatsapp.default_provider' => 'fonnte',
        'notifications.channels.whatsapp.country_code' => '62',
    ]);
    $dispatcher = app(NotificationDispatcher::class);

    $firstDelivery = $dispatcher->sendWhatsApp(
        messageType: 'order_confirmation',
        recipient: '0812-3456-7890',
        message: 'Pesanan ORDER-1001 sudah diterima.',
        idempotencyKey: 'order:1001:confirmation',
        context: ['order_reference' => 'ORDER-1001'],
    );
    $secondDelivery = $dispatcher->sendWhatsApp(
        messageType: 'order_confirmation',
        recipient: '0812-3456-7890',
        message: 'Pesanan ORDER-1001 sudah diterima.',
        idempotencyKey: 'order:1001:confirmation',
    );

    expect($secondDelivery->is($firstDelivery))->toBeTrue()
        ->and(NotificationDelivery::query()->count())->toBe(1)
        ->and($firstDelivery->recipient)->toBe('6281234567890')
        ->and($firstDelivery->message)->toBe('Pesanan ORDER-1001 sudah diterima.')
        ->and(DB::table('notification_deliveries')->value('recipient'))->not->toContain('6281234567890')
        ->and(DB::table('notification_deliveries')->value('message'))->not->toContain('ORDER-1001');

    Queue::assertPushed(SendNotificationDelivery::class, 1);
});

it('rejects an empty recipient before storing or queueing a notification', function () {
    Queue::fake([SendNotificationDelivery::class]);

    expect(fn () => app(NotificationDispatcher::class)->sendWhatsApp(
        messageType: 'order_confirmation',
        recipient: '',
        message: 'Pesanan diterima.',
        idempotencyKey: 'order:1002:confirmation',
    ))->toThrow(InvalidArgumentException::class);

    expect(NotificationDelivery::query()->count())->toBe(0);
    Queue::assertNothingPushed();
});
