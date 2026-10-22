<?php

namespace App\Http\Controllers;

use App\Actions\Stripe\EnsureStripeCustomer;
use App\Contracts\StripeGateway;
use App\Services\Stripe\StripeAccountRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SetupIntentController extends Controller
{
    public function __invoke(
        Request $request,
        StripeAccountRegistry $accounts,
        EnsureStripeCustomer $ensureStripeCustomer,
        StripeGateway $stripe,
    ): JsonResponse {
        $account = $accounts->defaultAccount();
        $customer = $ensureStripeCustomer->handle($request->user(), $account);
        $setupIntent = $stripe->createSetupIntent($account, $customer->stripe_customer_id);

        return response()->json([
            'client_secret' => $setupIntent['client_secret'],
            'publishable_key' => $accounts->publishableKey($account),
        ]);
    }
}
