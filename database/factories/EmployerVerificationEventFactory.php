<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\VerificationDecision;
use App\Enums\VerificationSource;
use App\Models\EmployerProfile;
use App\Models\EmployerVerificationEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployerVerificationEvent>
 */
class EmployerVerificationEventFactory extends Factory
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
            'decision' => VerificationDecision::Verified,
            'source' => VerificationSource::Staff,
            'actor_id' => User::factory()->state(['role' => UserRole::Staff]),
            'reason' => null,
            'employer_message' => null,
        ];
    }

    /**
     * A revocation, which always carries an internal reason.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'decision' => VerificationDecision::Unverified,
            'reason' => fake()->sentence(),
        ]);
    }
}
