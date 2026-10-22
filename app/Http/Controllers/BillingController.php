<?php

namespace App\Http\Controllers;

use App\Models\StripePrice;
use App\Services\Stripe\StripeAccountRegistry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function index(Request $request, StripeAccountRegistry $accounts): Response
    {
        $account = $accounts->defaultAccount();
        $prices = StripePrice::query()
            ->with('product')
            ->whereBelongsTo($account, 'stripeAccount')
            ->where('is_active', true)
            ->whereNotNull('billing_interval')
            ->whereHas('product', fn ($query) => $query->where('is_active', true))
            ->orderBy('amount')
            ->orderBy('id')
            ->get()
            ->map(fn (StripePrice $price): array => [
                'id' => $price->id,
                'name' => $price->product->name,
                'description' => $price->product->description,
                'amount' => $price->amount,
                'currency' => $price->currency,
                'interval' => $price->billing_interval,
                'interval_count' => $price->interval_count,
            ]);

        $subscription = $request->user()->subscriptions()
            ->whereBelongsTo($account, 'stripeAccount')
            ->latest('id')
            ->first();

        return Inertia::render('billing/index', [
            'prices' => $prices,
            'subscription' => $subscription?->only([
                'status',
                'current_period_end',
                'cancel_at_period_end',
            ]),
            'publishableKey' => $accounts->publishableKey($account),
        ]);
    }
}
