<?php

use App\Actions\Stripe\EnsureStripeCustomer;
use App\Models\User;
use App\Services\Stripe\StripeAccountRegistry;

it('creates one Stripe customer when a user registers', function () {
    $stripe = useFakeStripe();

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'billing@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertDatabaseHas('stripe_customers', ['stripe_customer_id' => 'cus_test']);
    expect($stripe->customerCreationCount)->toBe(1);

    $user = User::query()->where('email', 'billing@example.com')->firstOrFail();
    $account = app(StripeAccountRegistry::class)->defaultAccount();
    app(EnsureStripeCustomer::class)->handle($user, $account);

    expect($stripe->customerCreationCount)->toBe(1);
});

it('rolls back registration when Stripe customer creation fails', function () {
    $stripe = useFakeStripe();
    $stripe->failCustomerCreation = true;

    $response = $this->from(route('register'))->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'failed@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('register'));
    $response->assertSessionHasErrors('email');
    $this->assertDatabaseMissing('users', ['email' => 'failed@example.com']);
    $this->assertGuest();
});
