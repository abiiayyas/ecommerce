<?php

use App\Actions\Inventory\ReleaseExpiredReservationsAction;
use App\Actions\Inventory\ReserveOrderStockAction;
use App\Enums\StockReservationStatus;
use App\Models\Order\Order;
use App\Models\Product\ProductFlat;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('reserves owned stock and releases an expired reservation', function () {
    $flat = ProductFlat::factory()->create(['stock' => 5, 'is_unlimited_stock' => false]);
    $order = Order::factory()->guest()->create();

    $reservation = app(ReserveOrderStockAction::class)->handle($order, $flat, 2);
    expect($flat->refresh()->stock)->toBe(3)
        ->and($reservation->status)->toBe(StockReservationStatus::Active);

    $reservation->update(['expires_at' => now()->subMinute()]);
    app(ReleaseExpiredReservationsAction::class)->handle();

    expect($reservation->refresh()->status)->toBe(StockReservationStatus::Released)
        ->and($flat->refresh()->stock)->toBe(5);
});
