<?php

namespace App\Jobs;

use App\Actions\Jobs\PublishJob;
use App\Enums\JobStatus;
use App\Models\Job;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Publishes one ad that was waiting on a capability gate, as part of the batch
 * a verification kicks off.
 *
 * One queued job per ad rather than one job for the lot, so the batch reports
 * real progress and a single ad that fails to index cannot take the others down
 * with it.
 *
 * Deliberately routed through PublishJob: a mass status update would bypass
 * Eloquent's events, so Scout would never fire and every ad would go live in
 * Postgres while staying absent from Typesense — live, and unfindable.
 */
class PublishPendingJob implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [1, 5, 10];

    /**
     * The ad, called `$ad` rather than `$job` because InteractsWithQueue
     * already owns a `$job` property — the queue's own job, not Kerjago's.
     * Promoting `Job $job` here is a fatal at class load.
     */
    public function __construct(public Job $ad) {}

    public function handle(PublishJob $publishJob): void
    {
        // Un-verifying cancels the batch. Without this check the jobs already
        // queued would keep publishing ads for a company that has just had its
        // permission taken away.
        if ($this->batch()?->cancelled()) {
            return;
        }

        // The ad may have moved since the batch was composed — the employer
        // could have pulled it back to Draft, or closed it. Only what is still
        // waiting on the gate should go live.
        if ($this->ad->status !== JobStatus::Pending) {
            return;
        }

        $publishJob->handle($this->ad);
    }

    public function failed(?Throwable $exception): void
    {
        // The batch counts the failure, but the count alone does not say which
        // ad or why, and the employer is now verified with an ad still parked.
        Log::error('Could not publish a pending job after verification.', [
            'job_id' => $this->ad->id,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
