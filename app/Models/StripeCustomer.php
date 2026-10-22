<?php

namespace App\Models;

use Database\Factories\StripeCustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $stripe_account_id
 * @property string $stripe_customer_id
 */
#[Fillable(['user_id', 'stripe_account_id', 'stripe_customer_id'])]
class StripeCustomer extends Model
{
    /** @use HasFactory<StripeCustomerFactory> */
    use HasFactory;

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
}
