<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(middleware: ['auth', 'verified'])->name('dashboard');
Route::get('/invoice/{invoice}/download', [DashboardController::class, 'downloadPdf'])
    ->middleware(['auth', 'verified'])
    ->where('invoice', '[0-9]+')
    ->name('invoice.download');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// PayPal-Routen
use App\Http\Controllers\PayPalController;
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/paypal/bulk/pay', [PayPalController::class, 'bulkPay'])->name('paypal.bulk.pay');
    Route::get('/paypal/bulk/overview', [PayPalController::class, 'bulkOverview'])->name('paypal.bulk.overview');
    Route::get('/paypal/bulk/success', [PayPalController::class, 'bulkSuccess'])->name('paypal.bulk.success');
    Route::get('/paypal/bulk/cancel', [PayPalController::class, 'bulkCancel'])->name('paypal.bulk.cancel');
    
    // Einzelne Rechnung PayPal-Routen
    Route::get('/paypal/{invoice}/success', [PayPalController::class, 'success'])->name('paypal.success');
    Route::get('/paypal/{invoice}/cancel', [PayPalController::class, 'cancel'])->name('paypal.cancel');
});

// Webhook-Route für Lexoffice (ohne /webhook prefix für API-Kompatibilität)
use App\Http\Controllers\LexofficeWebhookController;
Route::post('/api/lexoffice-webhook', [LexofficeWebhookController::class, 'handle']);
