<?php

namespace Database\Factories;

use App\Models\JobseekerProfile;
use App\Models\WorkExperience;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkExperience>
 */
class WorkExperienceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-10 years', '-2 years');

        return [
            'jobseeker_profile_id' => JobseekerProfile::factory(),
            'job_title' => fake()->randomElement([
                'Junior Backend Engineer',
                'Junior Frontend Developer',
                'Software Engineer',
                'Web Developer',
                'Mobile Developer',
                'QA Analyst',
                'IT Support Specialist',
                'Graphic Designer',
                'Marketing Executive',
                'Account Manager',
            ]),
            'company_name' => fake()->company(),
            'start_date' => $start,
            'end_date' => fake()->dateTimeBetween($start, '-1 month'),
            'sort' => 0,
        ];
    }

    /**
     * Indicate that this is the person's current role.
     */
    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'end_date' => null,
        ]);
    }
}
