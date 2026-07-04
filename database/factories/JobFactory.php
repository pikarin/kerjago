<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Enums\JobStatus;
use App\Models\EmployerProfile;
use App\Models\Job;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Job>
 */
class JobFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var array<string, array{Currency, int, int}> $markets */
        $markets = [
            'ID' => [Currency::Idr, 5_000_000, 60_000_000],
            'SG' => [Currency::Sgd, 3_000, 18_000],
            'MY' => [Currency::Myr, 3_000, 25_000],
            'PH' => [Currency::Php, 25_000, 250_000],
            'VN' => [Currency::Vnd, 10_000_000, 120_000_000],
            'TH' => [Currency::Thb, 25_000, 250_000],
        ];

        $country = array_rand($markets);
        [$currency, $floor, $ceiling] = $markets[$country];

        $salaryMin = fake()->numberBetween($floor, intdiv($ceiling, 2));

        return [
            'employer_profile_id' => EmployerProfile::factory(),
            'title' => fake()->jobTitle(),
            'description' => fake()->paragraphs(3, true),
            'skills' => fake()->randomElements(
                ['PHP', 'Laravel', 'Vue.js', 'React', 'TypeScript', 'Python', 'Go', 'MySQL', 'PostgreSQL', 'Redis', 'Docker', 'AWS', 'Figma', 'SEO', 'Copywriting'],
                fake()->numberBetween(2, 5)
            ),
            'location_country' => $country,
            'location_city' => fake()->city(),
            'salary_min' => $salaryMin,
            'salary_max' => fake()->numberBetween($salaryMin, $ceiling),
            'currency' => $currency,
            'status' => JobStatus::Active,
        ];
    }

    /**
     * Indicate that the job is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JobStatus::Draft,
        ]);
    }

    /**
     * Indicate that the job is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JobStatus::Closed,
        ]);
    }
}
