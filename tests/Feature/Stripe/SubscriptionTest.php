<?php

use App\Models\PaymentMethod;
use App\Models\StripeCustomer;
use App\Models\StripePrice;
use App\Models\StripeProduct;
use App\Models\User;
use App\Services\Stripe\StripeAccountRegistry;

it('creates an account-scoped subscription and grants Pro access', function () {
    useFakeStripe();
    $user = User::factory()->create();
    $account = app(StripeAccountRegistry::class)->defaultAccount();
    StripeCustomer::factory()->for($user)->for($account, 'stripeAccount')->create([
        'stripe_customer_id' => 'cus_test',
    ]);
    PaymentMethod::factory()->for($user)->for($account, 'stripeAccount')->create([
        'stripe_customer_id' => 'cus_test',
        'stripe_payment_method_id' => 'pm_test',
        'is_default' => true,
    ]);
    $product = StripeProduct::factory()->for($account, 'stripeAccount')->create();
    $price = StripePrice::factory()->for($account, 'stripeAccount')->for($product, 'product')->create([
        'stripe_price_id' => 'price_monthly',
    ]);

    $response = $this->actingAs($user)->postJson(route('billing.subscriptions.store'), [
        'price_id' => $price->id,
        'idempotency_key' => fake()->uuid(),
    ]);

    $response->assertOk()->assertJsonPath('status', 'active');
    $this->assertDatabaseHas('subscriptions', [
        'user_id' => $user->id,
        'stripe_account_id' => $account->id,
        'stripe_subscription_id' => 'sub_test',
        'status' => 'active',
    ]);
    expect($user->hasProAccess())->toBeTrue();
});

it('rejects a repeated subscription while one is open', function () {
    useFakeStripe();
    $user = User::factory()->create();
    $account = app(StripeAccountRegistry::class)->defaultAccount();
    StripeCustomer::factory()->for($user)->for($account, 'stripeAccount')->create([
        'stripe_customer_id' => 'cus_test',
    ]);
    PaymentMethod::factory()->for($user)->for($account, 'stripeAccount')->create([
        'stripe_customer_id' => 'cus_test',
        'stripe_payment_method_id' => 'pm_test',
        'is_default' => true,
    ]);
    $product = StripeProduct::factory()->for($account, 'stripeAccount')->create();
    $price = StripePrice::factory()->for($account, 'stripeAccount')->for($product, 'product')->create();

    $payload = ['price_id' => $price->id, 'idempotency_key' => fake()->uuid()];
    $this->actingAs($user)->postJson(route('billing.subscriptions.store'), $payload)->assertOk();

    $this->actingAs($user)->postJson(route('billing.subscriptions.store'), [
        ...$payload,
        'idempotency_key' => fake()->uuid(),
    ])->assertUnprocessable()->assertJsonValidationErrors('subscription');

    $this->assertDatabaseCount('subscriptions', 1);
});

it('keeps Pro access disabled while SCA payment confirmation is incomplete', function () {
    $stripe = useFakeStripe();
    $user = User::factory()->create();
    $account = app(StripeAccountRegistry::class)->defaultAccount();
    StripeCustomer::factory()->for($user)->for($account, 'stripeAccount')->create([
        'stripe_customer_id' => 'cus_test',
    ]);
    PaymentMethod::factory()->for($user)->for($account, 'stripeAccount')->create([
        'stripe_customer_id' => 'cus_test',
        'stripe_payment_method_id' => 'pm_test',
        'is_default' => true,
    ]);
    $product = StripeProduct::factory()->for($account, 'stripeAccount')->create();
    $price = StripePrice::factory()->for($account, 'stripeAccount')->for($product, 'product')->create([
        'stripe_price_id' => 'price_sca',
    ]);
    $stripe->subscription = [
        'id' => 'sub_incomplete',
        'customer' => 'cus_test',
        'status' => 'incomplete',
        'cancel_at_period_end' => false,
        'canceled_at' => null,
        'trial_end' => null,
        'items' => [
            'data' => [[
                'price' => ['id' => 'price_sca'],
                'current_period_start' => now()->timestamp,
                'current_period_end' => now()->addMonth()->timestamp,
            ]],
        ],
        'latest_invoice' => [
            'confirmation_secret' => ['client_secret' => 'pi_secret_sca'],
        ],
    ];

    $response = $this->actingAs($user)->postJson(route('billing.subscriptions.store'), [
        'price_id' => $price->id,
        'idempotency_key' => fake()->uuid(),
    ]);

    $response->assertOk()
        ->assertJsonPath('status', 'incomplete')
        ->assertJsonPath('client_secret', 'pi_secret_sca');
    expect($user->hasProAccess())->toBeFalse();
});
