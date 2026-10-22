<?php

namespace App\Http\Controllers;

use App\Actions\Stripe\CreateSubscription;
use App\Exceptions\StripeOperationException;
use App\Http\Requests\StoreSubscriptionRequest;
use App\Models\StripePrice;
use App\Services\Stripe\StripeAccountRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class SubscriptionController extends Controller
{
    public function store(
        StoreSubscriptionRequest $request,
        StripeAccountRegistry $accounts,
        CreateSubscription $createSubscription,
    ): JsonResponse {
        $account = $accounts->defaultAccount();
        $price = StripePrice::query()
            ->whereBelongsTo($account, 'stripeAccount')
            ->findOrFail($request->integer('price_id'));

        try {
            $result = $createSubscription->handle(
                $request->user(),
                $account,
                $price,
                $request->string('idempotency_key')->toString(),
            );
        } catch (StripeOperationException $exception) {
            throw ValidationException::withMessages([
                'subscription' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'status' => $result['subscription']->status,
            'client_secret' => $result['client_secret'],
        ]);
    }
}
