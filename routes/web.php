<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use App\Http\Controllers\TelegramController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

Route::post('telegram/webhook', [TelegramController::class, 'handle'])->name('telegram.webhook');

use App\Livewire\HdDamanManager;
use App\Livewire\OrderTypeManager;
use App\Livewire\FalloutStatusManager;

Route::middleware(['auth', 'role:super-admin'])->group(function () {
    Route::get('/hd-damans', HdDamanManager::class)->name('hd-damans.index');
    Route::get('/order-types', OrderTypeManager::class)->name('order-types.index');
    Route::get('/fallout-statuses', FalloutStatusManager::class)->name('fallout-statuses.index');
});

require __DIR__.'/auth.php';

