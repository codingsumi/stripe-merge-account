<?php

use App\Models\StripeCustomer;
use App\Models\User;
use App\Services\Stripe\StripeAccountRegistry;

it('creates a SetupIntent and stores only card display metadata', function () {
    useFakeStripe();
    $user = User::factory()->create();
    $account = app(StripeAccountRegistry::class)->defaultAccount();
    StripeCustomer::factory()->for($user)->for($account, 'stripeAccount')->create([
        'stripe_customer_id' => 'cus_test',
    ]);

    $this->actingAs($user)
        ->postJson(route('billing.setup-intent.store'))
        ->assertOk()
        ->assertJson([
            'client_secret' => 'seti_test_secret',
            'publishable_key' => 'pk_test_fake',
        ]);

    $this->actingAs($user)
        ->postJson(route('billing.payment-methods.store'), [
            'setup_intent_id' => 'seti_test',
        ])
        ->assertOk()
        ->assertJsonPath('payment_method.card_last_four', '4242');

    $this->assertDatabaseHas('payment_methods', [
        'user_id' => $user->id,
        'stripe_account_id' => $account->id,
        'stripe_payment_method_id' => 'pm_test',
        'card_brand' => 'visa',
        'card_last_four' => '4242',
        'is_default' => true,
    ]);
});

it('requires authentication for payment setup', function () {
    useFakeStripe();

    $this->postJson(route('billing.setup-intent.store'))->assertUnauthorized();
    $this->postJson(route('billing.payment-methods.store'), [
        'setup_intent_id' => 'seti_test',
    ])->assertUnauthorized();
});
