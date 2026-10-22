<?php

namespace App\Http\Controllers;

use App\Actions\Stripe\EnsureStripeCustomer;
use App\Actions\Stripe\SyncPaymentMethod;
use App\Http\Requests\StorePaymentMethodRequest;
use App\Services\Stripe\StripeAccountRegistry;
use Illuminate\Http\JsonResponse;

class PaymentMethodController extends Controller
{
    public function store(
        StorePaymentMethodRequest $request,
        StripeAccountRegistry $accounts,
        EnsureStripeCustomer $ensureStripeCustomer,
        SyncPaymentMethod $syncPaymentMethod,
    ): JsonResponse {
        $account = $accounts->defaultAccount();
        $customer = $ensureStripeCustomer->handle($request->user(), $account);
        $paymentMethod = $syncPaymentMethod->fromSetupIntent(
            $customer,
            $account,
            $request->string('setup_intent_id')->toString(),
        );

        return response()->json([
            'payment_method' => $paymentMethod->only([
                'card_brand',
                'card_last_four',
                'expiration_month',
                'expiration_year',
            ]),
        ]);
    }
}
