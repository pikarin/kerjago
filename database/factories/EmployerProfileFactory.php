<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\EmployerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployerProfile>
 */
class EmployerProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Verified by default, so a test that has nothing to say about
     * verification says nothing about it. Tests that do care state it either
     * way explicitly.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => UserRole::Employer]),
            'company_name' => fake()->company(),
            'industry' => fake()->randomElement(['Technology', 'Finance', 'Healthcare', 'E-commerce', 'Manufacturing', 'Logistics', 'Education']),
            'country' => fake()->randomElement(['ID', 'SG', 'MY', 'PH', 'VN', 'TH']),
            'city' => fake()->city(),
            'website' => fake()->optional()->url(),
            'verified_at' => now(),
            'verified_by_id' => null,
        ];
    }

    /**
     * A company that has not cleared verification — a fresh signup, or one
     * staff has taken back out.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verified_at' => null,
            'verified_by_id' => null,
        ]);
    }

    /**
     * Explicit counterpart to unverified(), for tests that want to say which
     * side of the gate they are on rather than inherit it.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verified_at' => now(),
        ]);
    }

    /**
     * An unverified company that has asked to be reviewed.
     */
    public function verificationRequested(): static
    {
        return $this->unverified()->state(fn (array $attributes) => [
            'verification_requested_at' => now(),
        ]);
    }
}
