<?php

namespace App\Models;

use Database\Factories\PaymentMethodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $stripe_account_id
 * @property string $stripe_customer_id
 * @property string $stripe_payment_method_id
 * @property bool $is_default
 */
#[Fillable(['user_id', 'stripe_account_id', 'stripe_customer_id', 'stripe_payment_method_id', 'card_brand', 'card_last_four', 'expiration_month', 'expiration_year', 'is_default'])]
class PaymentMethod extends Model
{
    /** @use HasFactory<PaymentMethodFactory> */
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

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }
}
