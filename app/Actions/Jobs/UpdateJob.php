<?php

namespace App\Actions\Jobs;

use App\Models\Job;

class UpdateJob
{
    /**
     * Update an existing job opening, including status transitions.
     *
     * @param  array{title: string, description: string, skills: array<int, string>, location_country: string, location_city: string, salary_min: int, salary_max: int, currency: string, employment_type: string, work_arrangement: string, experience_level: string, education_level: string, status: string}  $data
     */
    public function handle(Job $job, array $data): Job
    {
        $job->update($data);

        return $job;
    }
}
