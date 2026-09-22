<?php

use App\Http\Controllers\LandingCheckoutController;
use App\Http\Controllers\LandingPageController;
use Illuminate\Support\Facades\Route;

Route::prefix('p/{landingPage:slug}')->name('landing.')->group(function (): void {
    Route::get('areas', [LandingPageController::class, 'areas'])
        ->middleware('throttle:60,1')
        ->name('areas');
    Route::post('rates', [LandingPageController::class, 'rates'])
        ->middleware('throttle:30,1')
        ->name('rates');
    Route::post('checkout', [LandingCheckoutController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('checkout');
});
