<?php

namespace App\Models;

use Database\Factories\StripeProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $stripe_account_id
 * @property string $stripe_product_id
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property array<string, mixed>|null $metadata
 */
#[Fillable(['stripe_account_id', 'stripe_product_id', 'name', 'description', 'is_active', 'metadata', 'last_synced_at'])]
class StripeProduct extends Model
{
    /** @use HasFactory<StripeProductFactory> */
    use HasFactory;

    /** @return BelongsTo<StripeAccount, $this> */
    public function stripeAccount(): BelongsTo
    {
        return $this->belongsTo(StripeAccount::class);
    }

    /** @return HasMany<StripePrice, $this> */
    public function prices(): HasMany
    {
        return $this->hasMany(StripePrice::class);
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
