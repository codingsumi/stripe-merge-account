<?php

namespace App\Services\Stripe;

use App\Contracts\StripeGateway;
use App\Exceptions\StripeOperationException;
use App\Models\StripeAccount;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;

class OfficialStripeGateway implements StripeGateway
{
    public function __construct(private StripeAccountRegistry $accounts) {}

    public function createCustomer(StripeAccount $account, string $name, string $email, string $idempotencyKey): array
    {
        return $this->call(fn () => $this->client($account)->customers->create([
            'name' => $name,
            'email' => $email,
        ], ['idempotency_key' => $idempotencyKey])->toArray());
    }

    public function createSetupIntent(StripeAccount $account, string $customerId): array
    {
        return $this->call(fn () => $this->client($account)->setupIntents->create([
            'customer' => $customerId,
            'payment_method_types' => ['card'],
            'usage' => 'off_session',
        ])->toArray());
    }

    public function retrieveSetupIntent(StripeAccount $account, string $setupIntentId): array
    {
        return $this->call(fn () => $this->client($account)->setupIntents->retrieve($setupIntentId)->toArray());
    }

    public function retrievePaymentMethod(StripeAccount $account, string $paymentMethodId): array
    {
        return $this->call(fn () => $this->client($account)->paymentMethods->retrieve($paymentMethodId)->toArray());
    }

    public function listPaymentMethods(StripeAccount $account, string $customerId): array
    {
        return $this->call(fn () => array_map(
            static fn ($paymentMethod): array => $paymentMethod->toArray(),
            $this->client($account)->paymentMethods->all([
                'customer' => $customerId,
                'type' => 'card',
                'limit' => 100,
            ])->data,
        ));
    }

    public function setDefaultPaymentMethod(StripeAccount $account, string $customerId, string $paymentMethodId): void
    {
        $this->call(fn () => $this->client($account)->customers->update($customerId, [
            'invoice_settings' => ['default_payment_method' => $paymentMethodId],
        ]));
    }

    public function listProducts(StripeAccount $account): iterable
    {
        return $this->iterate($account, 'products');
    }

    public function listPrices(StripeAccount $account): iterable
    {
        return $this->iterate($account, 'prices');
    }

    public function createSubscription(
        StripeAccount $account,
        string $customerId,
        string $priceId,
        string $paymentMethodId,
        string $idempotencyKey,
        int $userId,
    ): array {
        return $this->call(fn () => $this->client($account)->subscriptions->create([
            'customer' => $customerId,
            'items' => [['price' => $priceId]],
            'default_payment_method' => $paymentMethodId,
            'payment_behavior' => 'default_incomplete',
            'payment_settings' => ['save_default_payment_method' => 'on_subscription'],
            'metadata' => ['app_user_id' => (string) $userId],
            'expand' => ['latest_invoice.confirmation_secret'],
        ], ['idempotency_key' => $idempotencyKey])->toArray());
    }

    public function retrieveSubscription(StripeAccount $account, string $subscriptionId): array
    {
        return $this->call(fn () => $this->client($account)->subscriptions->retrieve($subscriptionId, [
            'expand' => ['latest_invoice.confirmation_secret'],
        ])->toArray());
    }

    public function retrieveInvoice(StripeAccount $account, string $invoiceId): array
    {
        return $this->call(fn () => $this->client($account)->invoices->retrieve($invoiceId)->toArray());
    }

    public function constructWebhookEvent(string $payload, string $signature, string $secret): array
    {
        try {
            return Webhook::constructEvent($payload, $signature, $secret)->toArray();
        } catch (SignatureVerificationException|\UnexpectedValueException $exception) {
            throw new StripeOperationException('The Stripe webhook signature is invalid.', previous: $exception);
        }
    }

    private function client(StripeAccount $account): StripeClient
    {
        return new StripeClient($this->accounts->secret($account));
    }

    /** @return iterable<int, array<string, mixed>> */
    private function iterate(StripeAccount $account, string $resource): iterable
    {
        try {
            $collection = $this->client($account)->{$resource}->all(['limit' => 100]);

            foreach ($collection->autoPagingIterator() as $item) {
                yield $item->toArray();
            }
        } catch (ApiErrorException $exception) {
            throw new StripeOperationException($exception->getMessage(), previous: $exception);
        }
    }

    private function call(callable $operation): mixed
    {
        try {
            return $operation();
        } catch (ApiErrorException $exception) {
            throw new StripeOperationException($exception->getMessage(), previous: $exception);
        }
    }
}
