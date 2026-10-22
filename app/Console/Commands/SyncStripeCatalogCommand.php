<?php

namespace App\Console\Commands;

use App\Jobs\SyncStripeCatalog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('stripe:sync-catalog')]
#[Description('Queue synchronization of Stripe products and prices')]
class SyncStripeCatalogCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        SyncStripeCatalog::dispatch((string) config('stripe.default'));
        $this->components->info('Stripe catalog synchronization was queued.');

        return self::SUCCESS;
    }
}
