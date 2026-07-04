<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\JobseekerProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $employer = User::factory()->employer()->create([
            'name' => 'Demo Employer',
            'email' => 'employer@example.com',
        ]);

        $employerProfile = EmployerProfile::factory()->for($employer)->create([
            'company_name' => 'Kerjago Demo Co',
            'country' => 'ID',
            'city' => 'Jakarta',
        ]);

        $jobseeker = User::factory()->jobseeker()->create([
            'name' => 'Demo Jobseeker',
            'email' => 'jobseeker@example.com',
        ]);

        $jobseekerProfile = JobseekerProfile::factory()->for($jobseeker)->create([
            'full_name' => 'Demo Jobseeker',
            'country' => 'ID',
            'city' => 'Jakarta',
        ]);

        $jobs = Job::factory(25)->recycle($employerProfile)->create();

        Application::factory()
            ->for($jobs->firstOrFail())
            ->for($jobseekerProfile)
            ->create();

        JobseekerProfile::factory(15)->create();
        Job::factory(5)->draft()->recycle($employerProfile)->create();
    }
}
