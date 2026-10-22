<?php

namespace Database\Factories;

use App\Models\StripeAccount;
use App\Models\StripeProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StripeProduct>
 */
class StripeProductFactory extends Factory
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
            'stripe_product_id' => 'prod_'.fake()->unique()->regexify('[A-Za-z0-9]{14}'),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'is_active' => true,
            'metadata' => [],
            'last_synced_at' => now(),
        ];
    }
}
