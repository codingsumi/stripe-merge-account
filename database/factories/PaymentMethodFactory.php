<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use App\Models\StripeAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
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
            'stripe_payment_method_id' => 'pm_'.fake()->unique()->regexify('[A-Za-z0-9]{14}'),
            'card_brand' => 'visa',
            'card_last_four' => '4242',
            'expiration_month' => 12,
            'expiration_year' => now()->addYears(3)->year,
            'is_default' => true,
        ];
    }
}
