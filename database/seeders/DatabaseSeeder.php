<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\JobseekerProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Skip per-model index syncing while seeding; the index is rebuilt
        // in one pass below so `migrate:fresh --seed` works out of the box.
        Job::withoutSyncingToSearch(function (): void {
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
            Job::factory(3)->legacy()->recycle($employerProfile)->create();
        });

        $this->rebuildSearchIndex();
    }

    /**
     * Drop and re-import the jobs search index so it exactly mirrors the
     * freshly seeded database (stale documents from a previous database
     * would otherwise survive a migrate:fresh).
     */
    private function rebuildSearchIndex(): void
    {
        if (config('scout.driver') !== 'typesense') {
            return;
        }

        // Import synchronously — queued sync jobs would leave the index
        // empty until a worker runs.
        config(['scout.queue' => false]);

        try {
            Artisan::call('scout:delete-index', ['name' => Job::class]);
        } catch (Throwable) {
            // The collection may not exist yet (first run) — import creates it.
        }

        try {
            Artisan::call('scout:import', ['model' => Job::class]);
            $this->command->info('Jobs search index rebuilt.');
        } catch (Throwable $e) {
            $this->command->warn(
                "Typesense unavailable, jobs were not indexed ({$e->getMessage()}). ".
                'Start it (`docker compose up -d typesense`) and run: php artisan scout:import "App\\Models\\Job"',
            );
        }
    }
}
