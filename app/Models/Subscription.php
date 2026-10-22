<?php

namespace App\Models;

use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $stripe_account_id
 * @property string $stripe_customer_id
 * @property string $stripe_subscription_id
 * @property string $stripe_price_id
 * @property string $status
 * @property Carbon|null $current_period_start
 * @property Carbon|null $current_period_end
 * @property Carbon|null $trial_end
 * @property bool $cancel_at_period_end
 * @property Carbon|null $canceled_at
 */
#[Fillable(['user_id', 'stripe_account_id', 'stripe_customer_id', 'stripe_subscription_id', 'stripe_price_id', 'status', 'current_period_start', 'current_period_end', 'trial_end', 'cancel_at_period_end', 'canceled_at', 'last_synced_at'])]
class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    public const ELIGIBLE_STATUSES = ['active', 'trialing'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<StripeAccount, $this> */
    public function stripeAccount(): BelongsTo
    {
        return $this->belongsTo(StripeAccount::class);
    }

    public function isEligible(): bool
    {
        return in_array($this->status, self::ELIGIBLE_STATUSES, true)
            && ($this->current_period_end === null || $this->current_period_end->isFuture());
    }

    protected function casts(): array
    {
        return [
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'trial_end' => 'datetime',
            'cancel_at_period_end' => 'boolean',
            'canceled_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }
}
