<?php

namespace App\Actions\Jobs;

use App\Models\EmployerProfile;
use App\Models\Job;

class CreateJob
{
    /**
     * Post a new job opening for the employer.
     *
     * @param  array{title: string, description: string, skills: array<int, string>, location_country: string, location_city: string, salary_min: int, salary_max: int, currency: string, employment_type: string, work_arrangement: string, experience_level: string, education_level: string, status: string}  $data
     */
    public function handle(EmployerProfile $employerProfile, array $data): Job
    {
        return $employerProfile->jobs()->create($data);
    }
}
