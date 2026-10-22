<?php

namespace App\Services\Stripe;

use App\Exceptions\StripeOperationException;
use App\Models\StripeAccount;

class StripeAccountRegistry
{
    public function defaultAccount(): StripeAccount
    {
        return $this->account((string) config('stripe.default'));
    }

    public function account(string $key): StripeAccount
    {
        $configuration = config("stripe.accounts.{$key}");

        if (! is_array($configuration)) {
            throw new StripeOperationException("Stripe account [{$key}] is not configured.");
        }

        return StripeAccount::query()->updateOrCreate(
            ['key' => $key],
            [
                'display_name' => (string) $configuration['name'],
                'mode' => (string) $configuration['mode'],
                'is_active' => (bool) $configuration['active'],
            ],
        );
    }

    /** @return array{name: string, mode: string, active: bool, secret: string|null, publishable_key: string|null, webhook_secret: string|null} */
    public function configuration(StripeAccount $account): array
    {
        /** @var array{name: string, mode: string, active: bool, secret: string|null, publishable_key: string|null, webhook_secret: string|null}|null $configuration */
        $configuration = config("stripe.accounts.{$account->key}");

        if ($configuration === null) {
            throw new StripeOperationException("Stripe account [{$account->key}] is not configured.");
        }

        return $configuration;
    }

    public function secret(StripeAccount $account): string
    {
        return $this->requiredCredential($account, 'secret');
    }

    public function publishableKey(StripeAccount $account): string
    {
        return $this->requiredCredential($account, 'publishable_key');
    }

    public function webhookSecret(StripeAccount $account): string
    {
        return $this->requiredCredential($account, 'webhook_secret');
    }

    private function requiredCredential(StripeAccount $account, string $key): string
    {
        $value = $this->configuration($account)[$key] ?? null;

        if (! is_string($value) || $value === '') {
            throw new StripeOperationException("Stripe credential [{$key}] is missing for account [{$account->key}].");
        }

        return $value;
    }
}
