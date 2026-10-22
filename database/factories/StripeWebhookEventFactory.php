<?php

namespace Database\Factories;

use App\Models\StripeAccount;
use App\Models\StripeWebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StripeWebhookEvent>
 */
class StripeWebhookEventFactory extends Factory
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
            'stripe_event_id' => 'evt_'.fake()->unique()->regexify('[A-Za-z0-9]{14}'),
            'event_type' => 'customer.subscription.updated',
            'status' => 'processed',
            'payload' => [],
            'error_details' => null,
            'processed_at' => now(),
        ];
    }
}
