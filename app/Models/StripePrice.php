<?php

namespace App\Models;

use Database\Factories\StripePriceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $stripe_account_id
 * @property int $stripe_product_id
 * @property string $stripe_price_id
 * @property int|null $amount
 * @property string $currency
 * @property string|null $billing_interval
 * @property int|null $interval_count
 * @property bool $is_active
 * @property StripeProduct $product
 */
#[Fillable(['stripe_account_id', 'stripe_product_id', 'stripe_price_id', 'amount', 'currency', 'billing_interval', 'interval_count', 'is_active', 'metadata', 'last_synced_at'])]
class StripePrice extends Model
{
    /** @use HasFactory<StripePriceFactory> */
    use HasFactory;

    /** @return BelongsTo<StripeAccount, $this> */
    public function stripeAccount(): BelongsTo
    {
        return $this->belongsTo(StripeAccount::class);
    }

    /** @return BelongsTo<StripeProduct, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(StripeProduct::class, 'stripe_product_id');
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata' => 'array',
            'last_synced_at' => 'datetime',
        ];
    }
}
