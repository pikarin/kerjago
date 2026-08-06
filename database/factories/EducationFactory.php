<?php

namespace Database\Factories;

use App\Enums\EducationLevel;
use App\Models\Education;
use App\Models\JobseekerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Education>
 */
class EducationFactory extends Factory
{
    /**
     * SEA institutions, so the seeded search demo returns plausible matches
     * instead of faker's US-shaped company names.
     *
     * @var list<string>
     */
    private const array INSTITUTIONS = [
        'Universitas Indonesia',
        'Institut Teknologi Bandung',
        'Universitas Gadjah Mada',
        'National University of Singapore',
        'Nanyang Technological University',
        'Universiti Malaya',
        'Universiti Teknologi Malaysia',
        'University of the Philippines',
        'Vietnam National University',
        'Chulalongkorn University',
    ];

    /**
     * @var list<string>
     */
    private const array FIELDS = [
        'Computer Science',
        'Information Systems',
        'Software Engineering',
        'Electrical Engineering',
        'Business Administration',
        'Marketing',
        'Visual Communication Design',
        'Statistics',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-14 years', '-6 years');

        return [
            'jobseeker_profile_id' => JobseekerProfile::factory(),
            'institution' => fake()->randomElement(self::INSTITUTIONS),
            'field_of_study' => fake()->randomElement(self::FIELDS),
            'level' => fake()->randomElement([
                EducationLevel::HighSchool,
                EducationLevel::Diploma,
                EducationLevel::Bachelor,
                EducationLevel::Master,
            ]),
            'start_date' => $start,
            'end_date' => fake()->dateTimeBetween($start, '-1 year'),
            'sort' => 0,
        ];
    }

    /**
     * A row the candidate entered without any dates — the common case the
     * UI renders as "Unspecified".
     */
    public function undated(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => null,
            'end_date' => null,
        ]);
    }
}
