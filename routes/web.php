<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DomainController;
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

// Domain management (only for customers)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/domains', [DomainController::class, 'index'])->name('domains.index');
    Route::get('/domains/{domain}', [DomainController::class, 'show'])->name('domains.show');
});

// Legal pages
Route::view('/legal/impressum', 'legal.impressum')->name('legal.impressum');
Route::view('/legal/privacy', 'legal.privacy')->name('legal.privacy');

require __DIR__.'/auth.php';

// Positionsverwaltung (nur für Administratoren)
use App\Http\Controllers\PositionController;
Route::middleware(['auth', 'verified', 'can:manage-positions'])->group(function () {
    Route::resource('positions', PositionController::class);
});

// PayPal-Routen
use App\Http\Controllers\PayPalController;
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/paypal/bulk/pay', [PayPalController::class, 'bulkPay'])->name('paypal.bulk.pay');
    Route::get('/paypal/bulk/success', [PayPalController::class, 'bulkSuccess'])->name('paypal.bulk.success');
    Route::get('/paypal/bulk/cancel', [PayPalController::class, 'bulkCancel'])->name('paypal.bulk.cancel');
    
    // Einzelne Rechnung PayPal-Routen
    Route::get('/paypal/{invoice}/success', [PayPalController::class, 'success'])->name('paypal.success');
    Route::get('/paypal/{invoice}/cancel', [PayPalController::class, 'cancel'])->name('paypal.cancel');
});

// Webhook-Route für Lexoffice (ohne /webhook prefix für API-Kompatibilität)
use App\Http\Controllers\LexofficeWebhookController;
Route::post('/api/lexoffice-webhook', [LexofficeWebhookController::class, 'handle']);
