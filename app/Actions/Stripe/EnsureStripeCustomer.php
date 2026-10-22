<?php

namespace App\Actions\Stripe;

use App\Contracts\StripeGateway;
use App\Models\StripeAccount;
use App\Models\StripeCustomer;
use App\Models\User;

class EnsureStripeCustomer
{
    public function __construct(private StripeGateway $stripe) {}

    public function handle(User $user, StripeAccount $account): StripeCustomer
    {
        $existingCustomer = $user->stripeCustomers()
            ->whereBelongsTo($account, 'stripeAccount')
            ->first();

        if ($existingCustomer !== null) {
            return $existingCustomer;
        }

        $customer = $this->stripe->createCustomer(
            $account,
            $user->name,
            $user->email,
            hash('sha256', "customer:{$account->key}:{$user->email}"),
        );

        StripeCustomer::query()->firstOrCreate(
            ['user_id' => $user->id, 'stripe_account_id' => $account->id],
            ['stripe_customer_id' => $customer['id']],
        );

        return StripeCustomer::query()
            ->whereBelongsTo($user)
            ->whereBelongsTo($account, 'stripeAccount')
            ->firstOrFail();
    }
}
