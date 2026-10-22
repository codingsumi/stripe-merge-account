<?php

namespace App\Models;

use Database\Factories\StripeAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $key
 * @property string $display_name
 * @property string $mode
 * @property bool $is_active
 */
#[Fillable(['key', 'display_name', 'mode', 'is_active'])]
class StripeAccount extends Model
{
    /** @use HasFactory<StripeAccountFactory> */
    use HasFactory;

    /** @return HasMany<StripeCustomer, $this> */
    public function customers(): HasMany
    {
        return $this->hasMany(StripeCustomer::class);
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
