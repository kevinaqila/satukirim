<?php

use App\Http\Controllers\PublicShippingController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Landing')->name('home');

Route::prefix('api/public')->middleware('throttle:30,1')->group(function () {
    Route::get('/areas', [PublicShippingController::class, 'searchAreas'])->name('public.areas');
    Route::match(['get', 'post'], '/rates', [PublicShippingController::class, 'calculateRates'])->name('public.rates');
    Route::get('/track', [PublicShippingController::class, 'trackWaybill'])->name('public.track');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
