<?php

namespace App\Actions\Stripe;

use App\Contracts\StripeGateway;
use App\Models\StripeAccount;
use App\Models\StripePrice;
use App\Models\StripeProduct;
use Illuminate\Support\Facades\DB;

class SynchronizeStripeCatalog
{
    public function __construct(private StripeGateway $stripe) {}

    public function handle(StripeAccount $account): void
    {
        $products = iterator_to_array($this->stripe->listProducts($account), false);
        $prices = iterator_to_array($this->stripe->listPrices($account), false);
        $synchronizedAt = now();

        DB::transaction(function () use ($account, $products, $prices, $synchronizedAt): void {
            $remoteProductIds = [];

            foreach ($products as $product) {
                $remoteProductIds[] = $product['id'];

                StripeProduct::query()->updateOrCreate(
                    ['stripe_account_id' => $account->id, 'stripe_product_id' => $product['id']],
                    [
                        'name' => $product['name'],
                        'description' => $product['description'] ?? null,
                        'is_active' => (bool) $product['active'],
                        'metadata' => $product['metadata'] ?? [],
                        'last_synced_at' => $synchronizedAt,
                    ],
                );
            }

            StripeProduct::query()
                ->whereBelongsTo($account, 'stripeAccount')
                ->when($remoteProductIds !== [], fn ($query) => $query->whereNotIn('stripe_product_id', $remoteProductIds))
                ->when($remoteProductIds === [], fn ($query) => $query)
                ->update(['is_active' => false, 'last_synced_at' => $synchronizedAt]);

            $remotePriceIds = [];

            foreach ($prices as $price) {
                $productId = is_array($price['product']) ? $price['product']['id'] : $price['product'];
                $product = StripeProduct::query()
                    ->whereBelongsTo($account, 'stripeAccount')
                    ->where('stripe_product_id', $productId)
                    ->first();

                if ($product === null) {
                    continue;
                }

                $remotePriceIds[] = $price['id'];
                $recurring = is_array($price['recurring'] ?? null) ? $price['recurring'] : [];

                StripePrice::query()->updateOrCreate(
                    ['stripe_account_id' => $account->id, 'stripe_price_id' => $price['id']],
                    [
                        'stripe_product_id' => $product->id,
                        'amount' => $price['unit_amount'] ?? null,
                        'currency' => $price['currency'],
                        'billing_interval' => $recurring['interval'] ?? null,
                        'interval_count' => $recurring['interval_count'] ?? null,
                        'is_active' => (bool) $price['active'],
                        'metadata' => $price['metadata'] ?? [],
                        'last_synced_at' => $synchronizedAt,
                    ],
                );
            }

            StripePrice::query()
                ->whereBelongsTo($account, 'stripeAccount')
                ->when($remotePriceIds !== [], fn ($query) => $query->whereNotIn('stripe_price_id', $remotePriceIds))
                ->when($remotePriceIds === [], fn ($query) => $query)
                ->update(['is_active' => false, 'last_synced_at' => $synchronizedAt]);
        });
    }
}
