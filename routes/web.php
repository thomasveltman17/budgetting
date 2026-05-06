<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions');
Route::get('/history', [HistoryController::class, 'index'])->name('history');
Route::get('/history/{period}/transactions', [HistoryController::class, 'viewPeriodTransactions'])->name('history.period.transactions');
Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
