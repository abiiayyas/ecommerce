<?php

use App\Http\Controllers\Api\V1\Callback\BiteshipController;
use App\Http\Controllers\Api\V1\Callback\MidtransController;
use App\Http\Controllers\Api\V1\Callback\PaywuzController;
use Illuminate\Support\Facades\Route;

// Callback URL for Midtrans
Route::post('/midtrans/callback', [MidtransController::class, 'callback'])->name('midtrans.callback');

// Callback URL for Paywuz
Route::post('/paywuz/callback', [PaywuzController::class, 'callback'])->name('paywuz.callback');

// Callback URL for Biteship
Route::post('/biteship/callback', [BiteshipController::class, 'callback'])->name('biteship.callback');
