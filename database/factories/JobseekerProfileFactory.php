<?php

namespace Database\Factories;

use App\Enums\Availability;
use App\Enums\Gender;
use App\Enums\Language;
use App\Enums\SalaryPeriod;
use App\Enums\UserRole;
use App\Models\Education;
use App\Models\JobseekerLanguage;
use App\Models\JobseekerProfile;
use App\Models\User;
use App\Models\WorkExperience;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<JobseekerProfile>
 */
class JobseekerProfileFactory extends Factory
{
    /**
     * Realistic titles (aligned with the skills pool below) so the seeded
     * search demo returns sensible semantic matches, unlike faker's random
     * jobTitle() values.
     *
     * @var list<string>
     */
    private const array JOB_TITLES = [
        'Backend Engineer',
        'Frontend Developer',
        'Full Stack Developer',
        'Laravel Developer',
        'Vue.js Developer',
        'Mobile Developer',
        'DevOps Engineer',
        'Data Analyst',
        'QA Engineer',
        'Product Designer',
        'UI/UX Designer',
        'Digital Marketer',
        'SEO Specialist',
        'Content Writer',
        'Product Manager',
    ];

    /**
     * City paired with the state/province it sits in, keyed by country.
     *
     * @var array<string, array<string, string>>
     */
    private const array CITIES = [
        'ID' => [
            'Jakarta' => 'DKI Jakarta',
            'Bandung' => 'Jawa Barat',
            'Surabaya' => 'Jawa Timur',
            'Yogyakarta' => 'DI Yogyakarta',
        ],
        'SG' => ['Singapore' => 'Central Region'],
        'MY' => [
            'Kuala Lumpur' => 'Wilayah Persekutuan Kuala Lumpur',
            'Penang' => 'Pulau Pinang',
            'Johor Bahru' => 'Johor',
        ],
        'PH' => [
            'Manila' => 'Metro Manila',
            'Cebu City' => 'Cebu',
            'Davao City' => 'Davao del Sur',
        ],
        'VN' => [
            'Ho Chi Minh City' => 'Ho Chi Minh',
            'Hanoi' => 'Hanoi',
            'Da Nang' => 'Da Nang',
        ],
        'TH' => [
            'Bangkok' => 'Bangkok',
            'Chiang Mai' => 'Chiang Mai',
            'Phuket' => 'Phuket',
        ],
    ];

    /**
     * The currency a candidate in each country states their expectation in.
     *
     * @var array<string, string>
     */
    private const array CURRENCY_BY_COUNTRY = [
        'ID' => 'IDR',
        'SG' => 'SGD',
        'MY' => 'MYR',
        'PH' => 'PHP',
        'VN' => 'VND',
        'TH' => 'THB',
    ];

    /**
     * Monthly expectation floor per currency, in whole major units (ADR 0005).
     *
     * @var array<string, int>
     */
    private const array SALARY_FLOOR = [
        'IDR' => 8_000_000,
        'SGD' => 4_000,
        'MYR' => 5_000,
        'PHP' => 40_000,
        'VND' => 20_000_000,
        'THB' => 40_000,
    ];

    /**
     * A country paired with one of its own cities and that city's state.
     *
     * Drawn with array_rand rather than fake()->randomElement() because the
     * latter returns mixed, which cannot be used as an array key to look the
     * cities back up.
     *
     * @return array{country: string, state: string, city: string}
     */
    private static function randomLocation(): array
    {
        $country = array_rand(self::CITIES);
        $cities = self::CITIES[$country];
        $city = array_rand($cities);

        return [
            'country' => $country,
            'state' => $cities[$city],
            'city' => $city,
        ];
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $location = self::randomLocation();
        $preferred = self::randomLocation();
        $currency = self::CURRENCY_BY_COUNTRY[$location['country']];
        $salaryMin = self::SALARY_FLOOR[$currency] * fake()->numberBetween(1, 4);

        return [
            'user_id' => User::factory()->state(['role' => UserRole::Jobseeker]),
            'full_name' => fake()->name(),
            'current_title' => fake()->randomElement(self::JOB_TITLES),
            'current_company' => fake()->company(),
            'preferred_job_title' => fake()->randomElement(self::JOB_TITLES),
            'summary' => fake()->paragraph(),
            'skills' => fake()->randomElements(
                ['PHP', 'Laravel', 'Vue.js', 'React', 'TypeScript', 'Python', 'Go', 'MySQL', 'PostgreSQL', 'Redis', 'Docker', 'AWS', 'Figma', 'SEO', 'Copywriting'],
                fake()->numberBetween(2, 5)
            ),
            'experience_years' => fake()->numberBetween(0, 20),
            'country' => $location['country'],
            'state' => $location['state'],
            'city' => $location['city'],
            'preferred_country' => $preferred['country'],
            'preferred_state' => $preferred['state'],
            'preferred_city' => $preferred['city'],
            'expected_salary_min' => $salaryMin,
            'expected_salary_max' => $salaryMin * 2,
            'expected_salary_currency' => $currency,
            'expected_salary_period' => SalaryPeriod::Monthly,
            'availability' => fake()->randomElement(Availability::cases()),
            'gender' => fake()->randomElement(Gender::cases()),
            'date_of_birth' => fake()->dateTimeBetween('-45 years', '-20 years'),
            'phone' => null,
            'whatsapp' => null,
            'avatar_path' => null,
            'resume_path' => null,
            'last_active_at' => fake()->dateTimeBetween('-6 months'),
            'profile_views_count' => fake()->numberBetween(0, 200),
            'resume_downloads_count' => fake()->numberBetween(0, 40),
            'employer_actions_count' => fake()->numberBetween(0, 60),
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

    /**
     * Attach an uploaded avatar.
     */
    public function withAvatar(): static
    {
        return $this->state(fn (array $attributes) => [
            'avatar_path' => 'avatars/'.Str::ulid().'.jpg',
        ]);
    }

    /**
     * Attach a small work history (one past role, one current).
     */
    public function withExperience(): static
    {
        return $this
            ->has(WorkExperience::factory(), 'workExperiences')
            ->has(WorkExperience::factory()->current()->state(['sort' => 1]), 'workExperiences');
    }

    /**
     * Attach a single degree.
     */
    public function withEducation(): static
    {
        return $this->has(Education::factory(), 'educations');
    }

    /**
     * Attach spoken languages, one row per distinct language so the unique
     * (profile, language) constraint holds.
     */
    public function withLanguages(int $count = 2): static
    {
        $languages = fake()->randomElements(Language::cases(), $count);
        $factory = $this;

        foreach ($languages as $index => $language) {
            $factory = $factory->has(
                JobseekerLanguage::factory()->state([
                    'language' => $language,
                    'sort' => $index,
                ]),
                'languages',
            );
        }

        return $factory;
    }

    /**
     * A profile filled in only as far as the required fields go — everything
     * optional is null, mirroring a candidate who abandoned onboarding.
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_company' => null,
            'preferred_job_title' => null,
            'summary' => null,
            'state' => null,
            'preferred_country' => null,
            'preferred_state' => null,
            'preferred_city' => null,
            'expected_salary_min' => null,
            'expected_salary_max' => null,
            'expected_salary_currency' => null,
            'expected_salary_period' => null,
            'availability' => null,
            'gender' => null,
            'date_of_birth' => null,
            'whatsapp' => null,
            'last_active_at' => null,
        ]);
    }
}
