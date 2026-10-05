<?php

use App\Http\Controllers\Api\OptionsController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Middleware\AuthenticateApiToken;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:60,1', AuthenticateApiToken::class])->group(function () {
    Route::get('/options', OptionsController::class)->name('api.options');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('api.transactions.store');
});
