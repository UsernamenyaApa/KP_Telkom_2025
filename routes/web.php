<?php

use App\Livewire\FalloutReportDetail;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use App\Http\Controllers\TelegramController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/fallout-reports', App\Livewire\FalloutReportDashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('fallout-reports.index');

Route::get('fallout-reports/{id}', FalloutReportDetail::class)
    ->middleware(['auth', 'verified'])
    ->name('fallout-reports.show');

Route::view('pelurusan', 'pelurusan.index')
    ->middleware(['auth', 'verified'])
    ->name('pelurusan.index');

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


// ▼▼▼ TAMBAHKAN RUTE BARU DI SINI ▼▼▼
Volt::route('verify-otp', 'auth.verify-otp')->name('otp.verify');


require __DIR__.'/auth.php';