<?php

namespace Tests\Fakes;

use App\Contracts\StripeGateway;
use App\Exceptions\StripeOperationException;
use App\Models\StripeAccount;

class FakeStripeGateway implements StripeGateway
{
    public bool $failCustomerCreation = false;

    public int $customerCreationCount = 0;

    /** @var array<int, array<string, mixed>> */
    public array $products = [];

    /** @var array<int, array<string, mixed>> */
    public array $prices = [];

    /** @var array<string, mixed> */
    public array $setupIntent = [
        'id' => 'seti_test',
        'client_secret' => 'seti_test_secret',
        'status' => 'succeeded',
        'customer' => 'cus_test',
        'payment_method' => 'pm_test',
    ];

    /** @var array<string, mixed> */
    public array $paymentMethod = [
        'id' => 'pm_test',
        'customer' => 'cus_test',
        'type' => 'card',
        'card' => [
            'brand' => 'visa',
            'last4' => '4242',
            'exp_month' => 12,
            'exp_year' => 2030,
        ],
    ];

    /** @var array<string, mixed> */
    public array $subscription = [];

    /** @var array<string, mixed> */
    public array $webhookEvent = [];

    public function createCustomer(StripeAccount $account, string $name, string $email, string $idempotencyKey): array
    {
        $this->customerCreationCount++;

        if ($this->failCustomerCreation) {
            throw new StripeOperationException('Stripe is unavailable.');
        }

        return ['id' => 'cus_test'];
    }

    public function createSetupIntent(StripeAccount $account, string $customerId): array
    {
        return [...$this->setupIntent, 'customer' => $customerId];
    }

    public function retrieveSetupIntent(StripeAccount $account, string $setupIntentId): array
    {
        return [...$this->setupIntent, 'id' => $setupIntentId];
    }

    public function retrievePaymentMethod(StripeAccount $account, string $paymentMethodId): array
    {
        return [...$this->paymentMethod, 'id' => $paymentMethodId];
    }

    public function listPaymentMethods(StripeAccount $account, string $customerId): array
    {
        return [$this->paymentMethod];
    }

    public function setDefaultPaymentMethod(StripeAccount $account, string $customerId, string $paymentMethodId): void
    {
        // The fake records state through the local payment method assertion.
    }

    public function listProducts(StripeAccount $account): iterable
    {
        yield from $this->products;
    }

    public function listPrices(StripeAccount $account): iterable
    {
        yield from $this->prices;
    }

    public function createSubscription(
        StripeAccount $account,
        string $customerId,
        string $priceId,
        string $paymentMethodId,
        string $idempotencyKey,
        int $userId,
    ): array {
        if ($this->subscription !== []) {
            return $this->subscription;
        }

        return [
            'id' => 'sub_test',
            'customer' => $customerId,
            'status' => 'active',
            'cancel_at_period_end' => false,
            'canceled_at' => null,
            'trial_end' => null,
            'items' => [
                'data' => [[
                    'price' => ['id' => $priceId],
                    'current_period_start' => now()->timestamp,
                    'current_period_end' => now()->addMonth()->timestamp,
                ]],
            ],
            'latest_invoice' => null,
        ];
    }

    public function retrieveSubscription(StripeAccount $account, string $subscriptionId): array
    {
        return [...$this->subscription, 'id' => $subscriptionId];
    }

    public function retrieveInvoice(StripeAccount $account, string $invoiceId): array
    {
        return ['id' => $invoiceId];
    }

    public function constructWebhookEvent(string $payload, string $signature, string $secret): array
    {
        if ($signature !== 'valid-signature') {
            throw new StripeOperationException('Invalid signature.');
        }

        return $this->webhookEvent;
    }
}
