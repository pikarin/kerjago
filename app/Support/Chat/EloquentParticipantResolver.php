<?php

namespace App\Support\Chat;

use App\Chat\Contracts\ParticipantResolver;
use App\Chat\Data\ParticipantData;
use App\Enums\UserRole;
use App\Models\User;

/**
 * Host-side identity lookup for the chat module.
 *
 * Fixed query count regardless of how many participants are asked for: one
 * users query plus its two eager loads. The contract is batch-shaped precisely
 * so this stays true once chat becomes a remote service.
 */
class EloquentParticipantResolver implements ParticipantResolver
{
    /**
     * @param  list<string>  $ids
     * @return array<string, ParticipantData>
     */
    public function resolve(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $resolved = [];

        // Narrowed deliberately. A bare select on users pulls password,
        // remember_token and the encrypted two-factor columns into memory for
        // every participant on every chat page load; displayName needs only
        // these three. users.id is retained so the hasOne profiles still match.
        $users = User::query()
            ->select(['id', 'name', 'role'])
            ->whereIn('id', $ids)
            ->with([
                'jobseekerProfile:id,user_id,full_name',
                'employerProfile:id,user_id,company_name',
            ])
            ->get();

        foreach ($users as $user) {
            $resolved[$user->id] = new ParticipantData(
                id: $user->id,
                name: $this->displayName($user),
            );
        }

        // The contract requires an entry for every requested id. Chat holds no
        // foreign key, so an id whose account is gone is normal, not an error.
        foreach ($ids as $id) {
            $resolved[$id] ??= ParticipantData::placeholder($id);
        }

        return $resolved;
    }

    /**
     * Employers appear as their company, jobseekers as their professional name.
     * Falls back to the account name when a profile is not filled in yet.
     */
    private function displayName(User $user): string
    {
        return match ($user->role) {
            UserRole::Employer => $user->employerProfile->company_name ?? $user->name,
            UserRole::Jobseeker => $user->jobseekerProfile->full_name ?? $user->name,
            UserRole::Staff => $user->name,
        };
    }
}
