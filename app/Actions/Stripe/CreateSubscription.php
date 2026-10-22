<?php

namespace App\Actions\Stripe;

use App\Contracts\StripeGateway;
use App\Exceptions\StripeOperationException;
use App\Models\PaymentMethod;
use App\Models\StripeAccount;
use App\Models\StripeCustomer;
use App\Models\StripePrice;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateSubscription
{
    public function __construct(
        private StripeGateway $stripe,
        private SynchronizeSubscription $synchronizeSubscription,
    ) {}

    /** @return array{subscription: Subscription, client_secret: string|null} */
    public function handle(
        User $user,
        StripeAccount $account,
        StripePrice $price,
        string $idempotencyKey,
    ): array {
        return DB::transaction(function () use ($user, $account, $price, $idempotencyKey): array {
            $customer = StripeCustomer::query()
                ->whereBelongsTo($user)
                ->whereBelongsTo($account, 'stripeAccount')
                ->lockForUpdate()
                ->firstOrFail();

            $hasOpenSubscription = Subscription::query()
                ->whereBelongsTo($user)
                ->whereBelongsTo($account, 'stripeAccount')
                ->whereIn('status', ['active', 'trialing', 'incomplete', 'past_due', 'paused'])
                ->exists();

            if ($hasOpenSubscription) {
                throw new StripeOperationException('You already have a subscription that requires attention.');
            }

            $paymentMethod = PaymentMethod::query()
                ->whereBelongsTo($user)
                ->whereBelongsTo($account, 'stripeAccount')
                ->where('is_default', true)
                ->first();

            if ($paymentMethod === null) {
                throw new StripeOperationException('Add a payment method before subscribing.');
            }

            $stripeSubscription = $this->stripe->createSubscription(
                $account,
                $customer->stripe_customer_id,
                $price->stripe_price_id,
                $paymentMethod->stripe_payment_method_id,
                $idempotencyKey,
                $user->id,
            );

            $subscription = $this->synchronizeSubscription->handle($account, $stripeSubscription);

            if ($subscription === null) {
                throw new StripeOperationException('The subscription could not be associated with your account.');
            }

            $latestInvoice = is_array($stripeSubscription['latest_invoice'] ?? null)
                ? $stripeSubscription['latest_invoice']
                : [];
            $confirmationSecret = is_array($latestInvoice['confirmation_secret'] ?? null)
                ? ($latestInvoice['confirmation_secret']['client_secret'] ?? null)
                : null;

            return [
                'subscription' => $subscription,
                'client_secret' => is_string($confirmationSecret) ? $confirmationSecret : null,
            ];
        });
    }
}
