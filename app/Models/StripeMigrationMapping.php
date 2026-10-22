<?php

namespace App\Models;

use Database\Factories\StripeMigrationMappingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['source_stripe_account_id', 'target_stripe_account_id', 'entity_type', 'source_entity_id', 'target_entity_id', 'status', 'error_details', 'last_processed_at'])]
class StripeMigrationMapping extends Model
{
    /** @use HasFactory<StripeMigrationMappingFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['last_processed_at' => 'datetime'];
    }
}
