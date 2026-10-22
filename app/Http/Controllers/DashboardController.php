<?php

namespace App\Http\Controllers;

use App\Actions\Stripe\SyncPaymentMethod;
use App\Contracts\StripeGateway;
use App\Exceptions\StripeOperationException;
use App\Services\Stripe\StripeAccountRegistry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        StripeAccountRegistry $accounts,
        StripeGateway $stripe,
        SyncPaymentMethod $syncPaymentMethod,
    ): Response {
        $account = $accounts->defaultAccount();
        $hasPaymentMethod = $request->user()->paymentMethods()
            ->whereBelongsTo($account, 'stripeAccount')
            ->exists();

        $customer = $request->user()->stripeCustomers()
            ->whereBelongsTo($account, 'stripeAccount')
            ->first();

        if (! $hasPaymentMethod && $customer !== null) {
            try {
                foreach ($stripe->listPaymentMethods($account, $customer->stripe_customer_id) as $index => $paymentMethod) {
                    $syncPaymentMethod->store($customer, $account, $paymentMethod, $index === 0);
                }

                $hasPaymentMethod = $request->user()->paymentMethods()
                    ->whereBelongsTo($account, 'stripeAccount')
                    ->exists();
            } catch (StripeOperationException $exception) {
                report($exception);
            }
        }

        return Inertia::render('dashboard', [
            'requiresPaymentMethod' => ! $hasPaymentMethod,
        ]);
    }
}
