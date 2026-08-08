{{--
    Live progress for the publish run a verification kicks off.

    Polled rather than pushed: the run is short and the panel is internal.
    Closing this modal cancels nothing — the batch is queued and finishes
    whether anyone is watching.

    Styled inline rather than with Tailwind utilities. The panel has no custom
    Vite theme, so it loads only Filament's prebuilt stylesheet — which ships
    `fi-*` component classes and none of the utilities a view like this one
    would otherwise reach for. `h-2`, `bg-gray-200` and `text-danger-600` do not
    exist there, and the bar would render as a zero-height invisible strip.
    Colours are `color-mix`ed against `currentColor` so both themes read.
--}}
<div wire:poll.3s style="display: grid; gap: 0.75rem;">
    @if ($batch === null)
        <p style="font-size: 0.875rem; opacity: 0.7;">
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

        <div style="height: 0.5rem; width: 100%; overflow: hidden; border-radius: 9999px; background-color: color-mix(in srgb, currentColor 15%, transparent);">
            <div
                style="height: 100%; border-radius: 9999px; background-color: var(--primary-600, #059669); transition: width 300ms ease; width: {{ $percentage }}%;"
            ></div>
        </div>

        <p style="font-size: 0.875rem;">
            {{ $published }} of {{ $batch->totalJobs }} ads published.
        </p>

        @if ($batch->failedJobs > 0)
            <p style="font-size: 0.875rem; color: var(--danger-600, #dc2626);">
                {{ $batch->failedJobs }} could not be published. They are still
                waiting, and the failure is in the application log.
            </p>
        @endif

        @if ($batch->cancelled())
            <p style="font-size: 0.875rem; color: var(--warning-600, #d97706);">
                This run was stopped — the company was unverified while it was
                still going.
            </p>
        @elseif ($batch->finished())
            <p style="font-size: 0.875rem; font-weight: 500; color: var(--success-600, #059669);">
                Finished. You can close this.
            </p>
        @else
            <p style="font-size: 0.875rem; opacity: 0.7;">
                Still working. You can close this and come back — it keeps
                going either way.
            </p>
        @endif
    @endif
</div>
