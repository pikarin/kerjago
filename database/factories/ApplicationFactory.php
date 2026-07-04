<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Job;
use App\Models\JobseekerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_id' => Job::factory(),
            'jobseeker_profile_id' => JobseekerProfile::factory(),
            'status' => ApplicationStatus::Submitted,
            'resume_path' => null,
            'cover_note' => fake()->optional()->paragraph(),
        ];
    }

    /**
     * Indicate that the application has been reviewed.
     */
    public function reviewed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ApplicationStatus::Reviewed,
        ]);
    }

    /**
     * Indicate that the candidate has been shortlisted.
     */
    public function shortlisted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ApplicationStatus::Shortlisted,
        ]);
    }

    /**
     * Indicate that the application was rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ApplicationStatus::Rejected,
        ]);
    }
}
