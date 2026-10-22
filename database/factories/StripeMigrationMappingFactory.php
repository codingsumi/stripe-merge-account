<?php

namespace Database\Factories;

use App\Models\StripeAccount;
use App\Models\StripeMigrationMapping;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StripeMigrationMapping>
 */
class StripeMigrationMappingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_stripe_account_id' => StripeAccount::factory(),
            'target_stripe_account_id' => StripeAccount::factory(),
            'entity_type' => 'customer',
            'source_entity_id' => 'cus_'.fake()->unique()->regexify('[A-Za-z0-9]{14}'),
            'target_entity_id' => null,
            'status' => 'pending',
            'error_details' => null,
            'last_processed_at' => null,
        ];
    }
}
