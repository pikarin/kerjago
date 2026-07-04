<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\JobseekerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<JobseekerProfile>
 */
class JobseekerProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => UserRole::Jobseeker]),
            'full_name' => fake()->name(),
            'current_title' => fake()->jobTitle(),
            'skills' => fake()->randomElements(
                ['PHP', 'Laravel', 'Vue.js', 'React', 'TypeScript', 'Python', 'Go', 'MySQL', 'PostgreSQL', 'Redis', 'Docker', 'AWS', 'Figma', 'SEO', 'Copywriting'],
                fake()->numberBetween(2, 5)
            ),
            'experience_years' => fake()->numberBetween(0, 20),
            'country' => fake()->randomElement(['ID', 'SG', 'MY', 'PH', 'VN', 'TH']),
            'city' => fake()->city(),
            'phone' => null,
            'resume_path' => null,
        ];
    }

    /**
     * Indicate that the profile has an uploaded resume.
     */
    public function withResume(): static
    {
        return $this->state(fn (array $attributes) => [
            'resume_path' => 'resumes/'.Str::ulid().'.pdf',
        ]);
    }
}
