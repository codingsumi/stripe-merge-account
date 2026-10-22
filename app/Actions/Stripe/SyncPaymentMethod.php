<?php

namespace App\Actions\Stripe;

use App\Contracts\StripeGateway;
use App\Exceptions\StripeOperationException;
use App\Models\PaymentMethod;
use App\Models\StripeAccount;
use App\Models\StripeCustomer;
use Illuminate\Support\Facades\DB;

class SyncPaymentMethod
{
    public function __construct(private StripeGateway $stripe) {}

    public function fromSetupIntent(StripeCustomer $customer, StripeAccount $account, string $setupIntentId): PaymentMethod
    {
        $setupIntent = $this->stripe->retrieveSetupIntent($account, $setupIntentId);

        if (($setupIntent['status'] ?? null) !== 'succeeded'
            || ($setupIntent['customer'] ?? null) !== $customer->stripe_customer_id
            || ! is_string($setupIntent['payment_method'] ?? null)) {
            throw new StripeOperationException('The payment method setup could not be verified.');
        }

        $paymentMethod = $this->stripe->retrievePaymentMethod($account, $setupIntent['payment_method']);

        return $this->store($customer, $account, $paymentMethod, true);
    }

    /** @param array<string, mixed> $paymentMethod */
    public function store(StripeCustomer $customer, StripeAccount $account, array $paymentMethod, bool $makeDefault): PaymentMethod
    {
        if (($paymentMethod['customer'] ?? null) !== $customer->stripe_customer_id) {
            throw new StripeOperationException('The payment method does not belong to this customer.');
        }

        return DB::transaction(function () use ($customer, $account, $paymentMethod, $makeDefault): PaymentMethod {
            if ($makeDefault) {
                PaymentMethod::query()
                    ->whereBelongsTo($customer->user)
                    ->whereBelongsTo($account, 'stripeAccount')
                    ->update(['is_default' => false]);
            }

            $card = is_array($paymentMethod['card'] ?? null) ? $paymentMethod['card'] : [];

            $storedPaymentMethod = PaymentMethod::query()->updateOrCreate(
                [
                    'stripe_account_id' => $account->id,
                    'stripe_payment_method_id' => $paymentMethod['id'],
                ],
                [
                    'user_id' => $customer->user_id,
                    'stripe_customer_id' => $customer->stripe_customer_id,
                    'card_brand' => $card['brand'] ?? null,
                    'card_last_four' => $card['last4'] ?? null,
                    'expiration_month' => $card['exp_month'] ?? null,
                    'expiration_year' => $card['exp_year'] ?? null,
                    'is_default' => $makeDefault,
                ],
            );

            if ($makeDefault) {
                $this->stripe->setDefaultPaymentMethod(
                    $account,
                    $customer->stripe_customer_id,
                    $storedPaymentMethod->stripe_payment_method_id,
                );
            }

            return $storedPaymentMethod;
        });
    }
}
