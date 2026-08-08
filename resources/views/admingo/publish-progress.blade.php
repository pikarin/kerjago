{{--
    Live progress for the publish run a verification kicks off.

    Polled rather than pushed: the run is short, the panel is internal, and a
    three-second poll costs one indexed lookup. Closing this modal cancels
    nothing — the batch is queued and finishes whether anyone is watching.
--}}
<div wire:poll.3s class="fi-section space-y-3">
    @if ($batch === null)
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Nothing was waiting to publish for {{ $company }}.
        </p>
    @else
        @php
            // A failure decrements pendingJobs just as a success does, so
            // "processed" is what drives the bar and "published" is what the
            // count reports — otherwise the run would read "5 of 5 published"
            // directly above "2 could not be published".
            $total = max($batch->totalJobs, 1);
            $processed = $batch->totalJobs - $batch->pendingJobs;
            $published = max($processed - $batch->failedJobs, 0);
            $percentage = (int) round(($processed / $total) * 100);
        @endphp

        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
            <div
                class="h-full rounded-full bg-primary-600 transition-all"
                style="width: {{ $percentage }}%"
            ></div>
        </div>

        <p class="text-sm text-gray-700 dark:text-gray-300">
            {{ $published }} of {{ $batch->totalJobs }} ads published.
        </p>

        @if ($batch->failedJobs > 0)
            <p class="text-sm text-danger-600 dark:text-danger-400">
                {{ $batch->failedJobs }} could not be published. They are still
                waiting, and the failure is in the application log.
            </p>
        @endif

        @if ($batch->cancelled())
            <p class="text-sm text-warning-600 dark:text-warning-400">
                This run was stopped — the company was unverified while it was
                still going.
            </p>
        @elseif ($batch->finished())
            <p class="text-sm font-medium text-success-600 dark:text-success-400">
                Finished. You can close this.
            </p>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Still working. You can close this and come back — it keeps
                going either way.
            </p>
        @endif
    @endif
</div>
