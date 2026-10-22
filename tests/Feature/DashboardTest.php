<?php

use App\Models\StripeCustomer;
use App\Models\User;
use App\Services\Stripe\StripeAccountRegistry;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('users with a Stripe payment method are not prompted for another card', function () {
    useFakeStripe();
    $user = User::factory()->create();
    $account = app(StripeAccountRegistry::class)->defaultAccount();
    StripeCustomer::factory()->for($user)->for($account, 'stripeAccount')->create([
        'stripe_customer_id' => 'cus_test',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('requiresPaymentMethod', false));
    $this->assertDatabaseHas('payment_methods', [
        'user_id' => $user->id,
        'stripe_payment_method_id' => 'pm_test',
    ]);
});
