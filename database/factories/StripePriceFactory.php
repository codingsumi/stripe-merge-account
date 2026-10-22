<?php

namespace Database\Factories;

use App\Models\StripeAccount;
use App\Models\StripePrice;
use App\Models\StripeProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StripePrice>
 */
class StripePriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stripe_account_id' => StripeAccount::factory(),
            'stripe_product_id' => StripeProduct::factory(),
            'stripe_price_id' => 'price_'.fake()->unique()->regexify('[A-Za-z0-9]{14}'),
            'amount' => fake()->randomElement([900, 1900, 4900]),
            'currency' => 'usd',
            'billing_interval' => 'month',
            'interval_count' => 1,
            'is_active' => true,
            'metadata' => [],
            'last_synced_at' => now(),
        ];
    }
}
