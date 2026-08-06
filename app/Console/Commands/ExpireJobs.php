<?php

namespace App\Console\Commands;

use App\Enums\JobStatus;
use App\Models\Job;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class ExpireJobs extends Command
{
    protected $signature = 'jobs:expire';

    protected $description = 'Close job ads whose 45-day window has run out';

    /**
     * Flip overdue ads to Expired.
     *
     * Saved one model at a time rather than in a mass update: the save is what
     * re-evaluates shouldBeSearchable() and removes the document from Typesense.
     * A mass update would leave expired ads in the search index.
     *
     * Correctness does not depend on this command running — scopeActive already
     * refuses ads past `expires_at`. What it buys is the index staying clean and
     * the status column reading true.
     */
    public function handle(): int
    {
        $expired = 0;

        Job::query()
            ->where('status', JobStatus::Active)
            ->where('expires_at', '<=', now())
            ->chunkById(100, function (Collection $jobs) use (&$expired): void {
                /** @var Collection<int, Job> $jobs */
                foreach ($jobs as $job) {
                    $job->status = JobStatus::Expired;
                    $job->save();
                    $expired++;
                }
            });

        $this->info("Expired {$expired} job(s).");

        return self::SUCCESS;
    }
}
