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
        ];
    }
}
