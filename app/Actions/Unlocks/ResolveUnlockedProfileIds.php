<?php

namespace App\Actions\Unlocks;

use App\Models\CandidateUnlock;
use App\Models\EmployerProfile;

/**
 * Which of these candidates may this employer see in full?
 *
 * One query per page, whatever the result count: the caller hands over every
 * profile id it is about to render and gets back the unlocked subset. That is
 * the whole reason masking is decided in the controller rather than inside the
 * Resource, where it would be one query per card.
 *
 * Results are memoized for the life of the instance — resolve this Action from
 * the container (scoped per request) and a page that renders the same candidate
 * in a list and again in a header pays for one lookup.
 */
class ResolveUnlockedProfileIds
{
    /**
     * Keyed by "{employerProfileId}:{jobseekerProfileId}" so one instance serving
     * two employers — Admingo, a console command — cannot answer for the wrong one.
     *
     * @var array<string, bool>
     */
    private array $resolved = [];

    /**
     * @param  list<string>  $jobseekerProfileIds
     * @return array<string, true> keyed by profile id, present only for unlocked ones
     */
    public function handle(EmployerProfile $employerProfile, array $jobseekerProfileIds): array
    {
        $wanted = array_values(array_unique($jobseekerProfileIds));

        $unknown = array_values(array_filter(
            $wanted,
            fn (string $profileId): bool => ! array_key_exists($this->key($employerProfile->id, $profileId), $this->resolved),
        ));

        if ($unknown !== []) {
            // Hydrated rather than plucked so the ids carry the model's declared
            // string type instead of arriving as mixed database values.
            $unlocked = CandidateUnlock::query()
                ->active()
                ->where('employer_profile_id', $employerProfile->id)
                ->whereIn('jobseeker_profile_id', $unknown)
                ->get(['id', 'jobseeker_profile_id'])
                ->map(fn (CandidateUnlock $unlock): string => $unlock->jobseeker_profile_id)
                ->all();

            foreach ($unknown as $profileId) {
                $this->resolved[$this->key($employerProfile->id, $profileId)] = false;
            }

            foreach ($unlocked as $profileId) {
                $this->resolved[$this->key($employerProfile->id, $profileId)] = true;
            }
        }

        $answer = [];

        foreach ($wanted as $profileId) {
            if ($this->resolved[$this->key($employerProfile->id, $profileId)]) {
                $answer[$profileId] = true;
            }
        }

        return $answer;
    }

    /**
     * Single-candidate convenience for detail pages. Shares the memo, so a page
     * that already resolved a list does not query again.
     */
    public function has(EmployerProfile $employerProfile, string $jobseekerProfileId): bool
    {
        return $this->handle($employerProfile, [$jobseekerProfileId]) !== [];
    }

    /**
     * Drop a memoized answer after the underlying permission changes.
     *
     * The memo caches "locked" as readily as "unlocked", so a request that
     * checked a candidate before unlocking them would otherwise keep seeing the
     * stale negative — and with a sync queue that request also runs the job
     * that opens their conversation.
     */
    public function forget(EmployerProfile $employerProfile, string $jobseekerProfileId): void
    {
        unset($this->resolved[$this->key($employerProfile->id, $jobseekerProfileId)]);
    }

    private function key(string $employerProfileId, string $jobseekerProfileId): string
    {
        return $employerProfileId.':'.$jobseekerProfileId;
    }
}
