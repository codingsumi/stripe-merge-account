<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\SetupIntentController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('billing/setup-intent', SetupIntentController::class)->name('billing.setup-intent.store');
    Route::post('billing/payment-methods', [PaymentMethodController::class, 'store'])->name('billing.payment-methods.store');
    Route::post('billing/subscriptions', [SubscriptionController::class, 'store'])->name('billing.subscriptions.store');
});

Route::post('stripe/webhooks/{accountKey}', StripeWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('stripe.webhooks.handle');

require __DIR__.'/settings.php';
