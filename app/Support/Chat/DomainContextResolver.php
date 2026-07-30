<?php

namespace App\Support\Chat;

use App\Chat\Contracts\ContextResolver;
use App\Chat\Data\ContextData;
use App\Enums\ChatContextType;
use App\Models\Application;
use App\Models\Job;

/**
 * Turns opaque (type, id) pairs from the chat module into labels and links.
 */
class DomainContextResolver implements ContextResolver
{
    /**
     * @param  list<string>  $ids
     * @return array<string, ContextData>
     */
    public function resolve(string $type, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        // An unrecognised type must not throw: the module may hold context
        // written by an older or newer version of this application.
        $resolved = match (ChatContextType::tryFrom($type)) {
            ChatContextType::Job => $this->resolveJobs($ids),
            ChatContextType::Application => $this->resolveApplications($ids),
            null => [],
        };

        foreach ($ids as $id) {
            $resolved[$id] ??= ContextData::placeholder($type, $id);
        }

        return $resolved;
    }

    /**
     * @param  list<string>  $ids
     * @return array<string, ContextData>
     */
    private function resolveJobs(array $ids): array
    {
        return Job::query()
            ->whereIn('id', $ids)
            ->get(['id', 'title'])
            ->mapWithKeys(fn (Job $job) => [
                $job->id => new ContextData(
                    type: ChatContextType::Job->value,
                    id: $job->id,
                    label: $job->title,
                    url: route('jobs.show', $job),
                ),
            ])
            ->all();
    }

    /**
     * Labelled by the job applied to — the application itself has no name a
     * participant would recognise.
     *
     * @param  list<string>  $ids
     * @return array<string, ContextData>
     */
    private function resolveApplications(array $ids): array
    {
        return Application::query()
            ->whereIn('id', $ids)
            ->with('job:id,title')
            ->get(['id', 'job_id'])
            ->mapWithKeys(fn (Application $application) => [
                $application->id => new ContextData(
                    type: ChatContextType::Application->value,
                    id: $application->id,
                    label: $application->job->title,
                    url: route('jobs.show', $application->job_id),
                ),
            ])
            ->all();
    }
}
