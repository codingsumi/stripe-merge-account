<?php

namespace App\Jobs;

use App\Actions\Stripe\SynchronizeStripeCatalog;
use App\Services\Stripe\StripeAccountRegistry;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncStripeCatalog implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 300];

    public int $uniqueFor = 3600;

    public function __construct(public string $accountKey) {}

    /**
     * Execute the job.
     */
    public function handle(StripeAccountRegistry $accounts, SynchronizeStripeCatalog $synchronize): void
    {
        $synchronize->handle($accounts->account($this->accountKey));
    }

    public function uniqueId(): string
    {
        return $this->accountKey;
    }
}
