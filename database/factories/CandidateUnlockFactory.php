<?php

namespace Database\Factories;

use App\Enums\UnlockSource;
use App\Models\CandidateUnlock;
use App\Models\EmployerProfile;
use App\Models\JobseekerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CandidateUnlock>
 */
class CandidateUnlockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employer_profile_id' => EmployerProfile::factory(),
            'jobseeker_profile_id' => JobseekerProfile::factory(),
            'job_id' => null,
            'source' => UnlockSource::AutoFirstTen,
            'expires_at' => now()->addYear(),
        ];
    }

    /**
     * An unlock whose year has run out — the candidate is masked again.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }
}
