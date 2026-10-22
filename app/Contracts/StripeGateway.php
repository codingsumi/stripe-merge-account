<?php

namespace App\Contracts;

use App\Models\StripeAccount;

interface StripeGateway
{
    /** @return array<string, mixed> */
    public function createCustomer(StripeAccount $account, string $name, string $email, string $idempotencyKey): array;

    /** @return array<string, mixed> */
    public function createSetupIntent(StripeAccount $account, string $customerId): array;

    /** @return array<string, mixed> */
    public function retrieveSetupIntent(StripeAccount $account, string $setupIntentId): array;

    /** @return array<string, mixed> */
    public function retrievePaymentMethod(StripeAccount $account, string $paymentMethodId): array;

    /** @return array<int, array<string, mixed>> */
    public function listPaymentMethods(StripeAccount $account, string $customerId): array;

    public function setDefaultPaymentMethod(StripeAccount $account, string $customerId, string $paymentMethodId): void;

    /** @return iterable<int, array<string, mixed>> */
    public function listProducts(StripeAccount $account): iterable;

    /** @return iterable<int, array<string, mixed>> */
    public function listPrices(StripeAccount $account): iterable;

    /** @return array<string, mixed> */
    public function createSubscription(
        StripeAccount $account,
        string $customerId,
        string $priceId,
        string $paymentMethodId,
        string $idempotencyKey,
        int $userId,
    ): array;

    /** @return array<string, mixed> */
    public function retrieveSubscription(StripeAccount $account, string $subscriptionId): array;

    /** @return array<string, mixed> */
    public function retrieveInvoice(StripeAccount $account, string $invoiceId): array;

    /** @return array<string, mixed> */
    public function constructWebhookEvent(string $payload, string $signature, string $secret): array;
}
