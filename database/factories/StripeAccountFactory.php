<?php

namespace Database\Factories;

use App\Models\StripeAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StripeAccount>
 */
class StripeAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'display_name' => fake()->company(),
            'mode' => 'test',
            'is_active' => true,
        ];
    }
}
