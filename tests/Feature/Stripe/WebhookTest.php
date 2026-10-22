<?php

use App\Models\StripeCustomer;
use App\Models\User;
use App\Services\Stripe\StripeAccountRegistry;

it('processes subscription webhooks idempotently and updates Pro status', function () {
    $stripe = useFakeStripe();
    $user = User::factory()->create();
    $account = app(StripeAccountRegistry::class)->defaultAccount();
    StripeCustomer::factory()->for($user)->for($account, 'stripeAccount')->create([
        'stripe_customer_id' => 'cus_test',
    ]);
    $stripe->webhookEvent = [
        'id' => 'evt_subscription_active',
        'type' => 'customer.subscription.updated',
        'data' => [
            'object' => [
                'id' => 'sub_webhook',
                'customer' => 'cus_test',
                'status' => 'active',
                'cancel_at_period_end' => false,
                'canceled_at' => null,
                'trial_end' => null,
                'items' => [
                    'data' => [[
                        'price' => ['id' => 'price_pro'],
                        'current_period_start' => now()->timestamp,
                        'current_period_end' => now()->addMonth()->timestamp,
                    ]],
                ],
            ],
        ],
    ];

    $headers = ['Stripe-Signature' => 'valid-signature'];
    $this->postJson(route('stripe.webhooks.handle', ['accountKey' => 'account_1']), [], $headers)->assertOk();
    $this->postJson(route('stripe.webhooks.handle', ['accountKey' => 'account_1']), [], $headers)->assertOk();

    $this->assertDatabaseCount('stripe_webhook_events', 1);
    $this->assertDatabaseCount('subscriptions', 1);
    expect($user->hasProAccess())->toBeTrue();

    $stripe->webhookEvent['id'] = 'evt_subscription_canceled';
    $stripe->webhookEvent['data']['object']['status'] = 'canceled';
    $stripe->webhookEvent['data']['object']['canceled_at'] = now()->timestamp;

    $this->postJson(route('stripe.webhooks.handle', ['accountKey' => 'account_1']), [], $headers)->assertOk();

    $this->assertDatabaseCount('stripe_webhook_events', 2);
    $this->assertDatabaseCount('subscriptions', 1);
    expect($user->hasProAccess())->toBeFalse();
});

it('rejects a webhook with an invalid signature', function () {
    useFakeStripe();

    $this->postJson(
        route('stripe.webhooks.handle', ['accountKey' => 'account_1']),
        [],
        ['Stripe-Signature' => 'invalid'],
    )->assertBadRequest();

    $this->assertDatabaseCount('stripe_webhook_events', 0);
});
