<?php

use App\Actions\Stripe\SynchronizeStripeCatalog;
use App\Services\Stripe\StripeAccountRegistry;

it('synchronizes products and recurring prices idempotently', function () {
    $stripe = useFakeStripe();
    $stripe->products = [[
        'id' => 'prod_pro',
        'name' => 'Pro',
        'description' => 'Everything in Pro',
        'active' => true,
        'metadata' => ['tier' => 'pro'],
    ]];
    $stripe->prices = [[
        'id' => 'price_monthly',
        'product' => 'prod_pro',
        'unit_amount' => 1900,
        'currency' => 'usd',
        'active' => true,
        'recurring' => ['interval' => 'month', 'interval_count' => 1],
        'metadata' => [],
    ]];
    $account = app(StripeAccountRegistry::class)->defaultAccount();
    $synchronize = app(SynchronizeStripeCatalog::class);

    $synchronize->handle($account);
    $synchronize->handle($account);

    $this->assertDatabaseCount('stripe_products', 1);
    $this->assertDatabaseCount('stripe_prices', 1);
    $this->assertDatabaseHas('stripe_prices', [
        'stripe_price_id' => 'price_monthly',
        'amount' => 1900,
        'billing_interval' => 'month',
        'is_active' => true,
    ]);
});

it('marks catalog records inactive when Stripe no longer returns them', function () {
    $stripe = useFakeStripe();
    $stripe->products = [[
        'id' => 'prod_retired',
        'name' => 'Retired',
        'description' => null,
        'active' => true,
        'metadata' => [],
    ]];
    $account = app(StripeAccountRegistry::class)->defaultAccount();
    $synchronize = app(SynchronizeStripeCatalog::class);
    $synchronize->handle($account);

    $stripe->products = [];
    $synchronize->handle($account);

    $this->assertDatabaseHas('stripe_products', [
        'stripe_product_id' => 'prod_retired',
        'is_active' => false,
    ]);
});
