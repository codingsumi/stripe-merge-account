<?php

namespace App\Actions\Stripe;

use App\Contracts\StripeGateway;
use App\Models\PaymentMethod;
use App\Models\StripeAccount;
use App\Models\StripeCustomer;

class ProcessStripeWebhook
{
    public function __construct(
        private StripeGateway $stripe,
        private SynchronizeSubscription $synchronizeSubscription,
        private SyncPaymentMethod $syncPaymentMethod,
    ) {}

    /** @param array<string, mixed> $event */
    public function handle(StripeAccount $account, array $event): void
    {
        $object = $event['data']['object'] ?? [];

        if (! is_array($object)) {
            return;
        }

        match ($event['type'] ?? '') {
            'customer.subscription.created',
            'customer.subscription.updated',
            'customer.subscription.deleted' => $this->synchronizeSubscription->handle($account, $object),
            'setup_intent.succeeded' => $this->syncSetupIntent($account, $object),
            'payment_method.attached',
            'payment_method.updated' => $this->syncAttachedPaymentMethod($account, $object),
            'payment_method.detached' => PaymentMethod::query()
                ->whereBelongsTo($account, 'stripeAccount')
                ->where('stripe_payment_method_id', $object['id'] ?? '')
                ->delete(),
            'customer.deleted' => $this->deleteCustomer($account, $object),
            'invoice.paid',
            'invoice.payment_failed',
            'invoice.payment_action_required' => $this->syncInvoiceSubscription($account, $object),
            'payment_intent.succeeded',
            'payment_intent.payment_failed',
            'payment_intent.processing' => $this->syncPaymentIntent($account, $object),
            default => null,
        };
    }

    /** @param array<string, mixed> $setupIntent */
    private function syncSetupIntent(StripeAccount $account, array $setupIntent): void
    {
        $customerId = $setupIntent['customer'] ?? null;

        if (! is_string($customerId) || ! is_string($setupIntent['id'] ?? null)) {
            return;
        }

        $customer = StripeCustomer::query()
            ->whereBelongsTo($account, 'stripeAccount')
            ->where('stripe_customer_id', $customerId)
            ->first();

        if ($customer !== null) {
            $this->syncPaymentMethod->fromSetupIntent($customer, $account, $setupIntent['id']);
        }
    }

    /** @param array<string, mixed> $paymentMethod */
    private function syncAttachedPaymentMethod(StripeAccount $account, array $paymentMethod): void
    {
        $customerId = $paymentMethod['customer'] ?? null;

        if (! is_string($customerId)) {
            return;
        }

        $customer = StripeCustomer::query()
            ->whereBelongsTo($account, 'stripeAccount')
            ->where('stripe_customer_id', $customerId)
            ->first();

        if ($customer !== null) {
            $this->syncPaymentMethod->store($customer, $account, $paymentMethod, false);
        }
    }

    /** @param array<string, mixed> $invoice */
    private function syncInvoiceSubscription(StripeAccount $account, array $invoice): void
    {
        $subscriptionId = $invoice['parent']['subscription_details']['subscription']
            ?? $invoice['subscription']
            ?? null;

        if (! is_string($subscriptionId)) {
            return;
        }

        $subscription = $this->stripe->retrieveSubscription($account, $subscriptionId);
        $this->synchronizeSubscription->handle($account, $subscription);
    }

    /** @param array<string, mixed> $paymentIntent */
    private function syncPaymentIntent(StripeAccount $account, array $paymentIntent): void
    {
        $invoiceId = $paymentIntent['invoice'] ?? null;

        if (! is_string($invoiceId)) {
            return;
        }

        $this->syncInvoiceSubscription($account, $this->stripe->retrieveInvoice($account, $invoiceId));
    }

    /** @param array<string, mixed> $customer */
    private function deleteCustomer(StripeAccount $account, array $customer): void
    {
        if (! is_string($customer['id'] ?? null)) {
            return;
        }

        PaymentMethod::query()
            ->whereBelongsTo($account, 'stripeAccount')
            ->where('stripe_customer_id', $customer['id'])
            ->delete();

        StripeCustomer::query()
            ->whereBelongsTo($account, 'stripeAccount')
            ->where('stripe_customer_id', $customer['id'])
            ->delete();
    }
}
