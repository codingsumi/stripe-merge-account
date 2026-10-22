<?php

namespace Database\Factories;

use App\Models\StripeAccount;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'stripe_account_id' => StripeAccount::factory(),
            'stripe_customer_id' => 'cus_'.fake()->unique()->regexify('[A-Za-z0-9]{14}'),
            'stripe_subscription_id' => 'sub_'.fake()->unique()->regexify('[A-Za-z0-9]{14}'),
            'stripe_price_id' => 'price_'.fake()->unique()->regexify('[A-Za-z0-9]{14}'),
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'trial_end' => null,
            'cancel_at_period_end' => false,
            'canceled_at' => null,
            'last_synced_at' => now(),
        ];
    }
}
