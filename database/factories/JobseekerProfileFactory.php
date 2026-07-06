<?php

namespace Database\Factories;

use App\Enums\Availability;
use App\Enums\EducationLevel;
use App\Enums\Gender;
use App\Enums\Language;
use App\Enums\UserRole;
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
     * @var array<string, list<string>>
     */
    private const array CITIES = [
        'ID' => ['Jakarta', 'Bandung', 'Surabaya', 'Yogyakarta'],
        'SG' => ['Singapore'],
        'MY' => ['Kuala Lumpur', 'Penang', 'Johor Bahru'],
        'PH' => ['Manila', 'Cebu City', 'Davao City'],
        'VN' => ['Ho Chi Minh City', 'Hanoi', 'Da Nang'],
        'TH' => ['Bangkok', 'Chiang Mai', 'Phuket'],
    ];

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
            'current_title' => fake()->randomElement(self::JOB_TITLES),
            'preferred_job_title' => fake()->randomElement(self::JOB_TITLES),
            'skills' => fake()->randomElements(
                ['PHP', 'Laravel', 'Vue.js', 'React', 'TypeScript', 'Python', 'Go', 'MySQL', 'PostgreSQL', 'Redis', 'Docker', 'AWS', 'Figma', 'SEO', 'Copywriting'],
                fake()->numberBetween(2, 5)
            ),
            'experience_years' => fake()->numberBetween(0, 20),
            'country' => $country = fake()->randomElement(array_keys(self::CITIES)),
            'city' => fake()->randomElement(self::CITIES[$country]),
            'preferred_country' => $preferredCountry = fake()->randomElement(array_keys(self::CITIES)),
            'preferred_city' => fake()->randomElement(self::CITIES[$preferredCountry]),
            'availability' => fake()->randomElement(Availability::cases()),
            'languages' => fake()->randomElements(
                array_column(Language::cases(), 'value'),
                fake()->numberBetween(1, 3)
            ),
            'gender' => fake()->randomElement(Gender::cases()),
            'education_level' => fake()->randomElement(EducationLevel::cases()),
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
     * A profile created before the preference fields existed — everything
     * optional is null, mirroring legacy production rows.
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'preferred_job_title' => null,
            'preferred_country' => null,
            'preferred_city' => null,
            'availability' => null,
            'languages' => null,
            'gender' => null,
            'education_level' => null,
        ]);
    }
}
