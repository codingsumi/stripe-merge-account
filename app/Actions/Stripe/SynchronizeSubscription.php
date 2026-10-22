<?php

namespace App\Actions\Stripe;

use App\Models\StripeAccount;
use App\Models\StripeCustomer;
use App\Models\Subscription;
use Illuminate\Support\Carbon;

class SynchronizeSubscription
{
    /** @param array<string, mixed> $stripeSubscription */
    public function handle(StripeAccount $account, array $stripeSubscription): ?Subscription
    {
        $customerId = is_array($stripeSubscription['customer'] ?? null)
            ? $stripeSubscription['customer']['id']
            : ($stripeSubscription['customer'] ?? null);

        if (! is_string($customerId)) {
            return null;
        }

        $customer = StripeCustomer::query()
            ->whereBelongsTo($account, 'stripeAccount')
            ->where('stripe_customer_id', $customerId)
            ->first();

        if ($customer === null) {
            return null;
        }

        $item = $stripeSubscription['items']['data'][0] ?? [];
        $price = is_array($item['price'] ?? null) ? $item['price']['id'] : ($item['price'] ?? '');

        return Subscription::query()->updateOrCreate(
            [
                'stripe_account_id' => $account->id,
                'stripe_subscription_id' => $stripeSubscription['id'],
            ],
            [
                'user_id' => $customer->user_id,
                'stripe_customer_id' => $customerId,
                'stripe_price_id' => $price,
                'status' => $stripeSubscription['status'],
                'current_period_start' => $this->timestamp($item['current_period_start'] ?? null),
                'current_period_end' => $this->timestamp($item['current_period_end'] ?? null),
                'trial_end' => $this->timestamp($stripeSubscription['trial_end'] ?? null),
                'cancel_at_period_end' => (bool) ($stripeSubscription['cancel_at_period_end'] ?? false),
                'canceled_at' => $this->timestamp($stripeSubscription['canceled_at'] ?? null),
                'last_synced_at' => now(),
            ],
        );
    }

    private function timestamp(mixed $timestamp): ?Carbon
    {
        return is_int($timestamp) ? Carbon::createFromTimestamp($timestamp) : null;
    }
}
