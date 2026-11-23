<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\BiteshipController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicShippingController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\XenditController;
use App\Http\Middleware\EnsureSuperAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Landing')->name('home');

Route::middleware('guest')->prefix('auth/google')->name('auth.google.')->group(function () {
    Route::get('/redirect', [GoogleAuthController::class, 'redirect'])->name('redirect');
    Route::get('/callback', [GoogleAuthController::class, 'callback'])->name('callback');
});

Route::prefix('api/public')->middleware('throttle:30,1')->group(function () {
    Route::get('/areas', [PublicShippingController::class, 'searchAreas'])->name('public.areas');
    Route::match(['get', 'post'], '/rates', [PublicShippingController::class, 'calculateRates'])->name('public.rates');
    Route::get('/track', [PublicShippingController::class, 'trackWaybill'])->name('public.track');
});

Route::post('/api/biteship/webhook', [BiteshipController::class, 'webhook'])->name('biteship.webhook');
Route::post('/api/xendit/webhook', [XenditController::class, 'webhook'])->name('xendit.webhook');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('/stores', [StoreController::class, 'index'])->name('stores.index');
    Route::get('/stores/create', [StoreController::class, 'create'])->name('stores.create');
    Route::post('/stores', [StoreController::class, 'store'])->name('stores.store');
    Route::get('/stores/{store}/edit', [StoreController::class, 'edit'])->name('stores.edit');
    Route::put('/stores/{store}', [StoreController::class, 'update'])->name('stores.update');
    Route::delete('/stores/{store}', [StoreController::class, 'destroy'])->name('stores.destroy');
    Route::patch('/stores/{store}/default', [StoreController::class, 'setDefault'])->name('stores.default');

    Route::get('/shipments', [ShipmentController::class, 'index'])->name('shipments.index');
    Route::get('/shipments/create', [ShipmentController::class, 'create'])->name('shipments.create');
    Route::post('/shipments', [ShipmentController::class, 'store'])->name('shipments.store');
    Route::match(['get', 'post'], '/shipments/rates', [ShipmentController::class, 'rates'])->middleware('throttle:60,1')->name('shipments.rates');
    Route::post('/shipments/bulk-delete', [ShipmentController::class, 'bulkDestroy'])->name('shipments.bulk-delete');
    Route::get('/shipments/export-all', [ShipmentController::class, 'exportAll'])->name('shipments.export-all');
    Route::get('/shipments/print-bulk', [ShipmentController::class, 'printBulk'])->name('shipments.print-bulk');
    Route::get('/shipments/{shipment}', [ShipmentController::class, 'show'])->name('shipments.show');
    Route::delete('/shipments/{shipment}', [ShipmentController::class, 'destroy'])->name('shipments.destroy');
    Route::post('/shipments/{shipment}/pay', [ShipmentController::class, 'payWithXendit'])->name('shipments.pay');

    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/withdraw', [WalletController::class, 'withdraw'])->name('wallet.withdraw');

    Route::middleware([EnsureSuperAdmin::class])->prefix('admin')->name('admin.')->group(function () {
        Route::patch('/users/bulk-toggle', [AdminController::class, 'bulkToggleStatus'])->name('users.bulk-toggle');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::get('/users/create', [AdminController::class, 'createUser'])->name('users.create');
        Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
        Route::patch('/users/{user}/toggle', [AdminController::class, 'toggleUserStatus'])->name('users.toggle');
        Route::get('/shipments/export-all', [AdminController::class, 'exportAllShipments'])->name('shipments.export-all');
        Route::get('/transactions', [AdminController::class, 'transactions'])->name('transactions');
        Route::get('/audit-logs', [AdminController::class, 'auditLogs'])->name('audit-logs');
    });
});

require __DIR__.'/settings.php';

Route::fallback(function (Request $request) {
    if ($request->is('api/*') || $request->expectsJson()) {
        return response()->json(['message' => 'Not found.'], 404);
    }

    abort(404);
});
