<?php

namespace Database\Factories;

use App\Models\StripeAccount;
use App\Models\StripeCustomer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StripeCustomer>
 */
class StripeCustomerFactory extends Factory
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
        ];
    }
}
