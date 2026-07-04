<?php

namespace App\Actions\Profiles;

use App\Models\EmployerProfile;
use App\Models\User;

class UpsertEmployerProfile
{
    /**
     * Create or update the employer's company profile.
     *
     * @param  array{company_name: string, industry: string, country: string, city: string, website: string|null}  $data
     */
    public function handle(User $user, array $data): EmployerProfile
    {
        $profile = $user->employerProfile ?? new EmployerProfile;

        $profile->fill($data);
        $profile->user()->associate($user);
        $profile->save();

        return $profile;
    }
}
