<?php

namespace Database\Factories;

use App\Enums\Language;
use App\Enums\LanguageProficiency;
use App\Models\JobseekerLanguage;
use App\Models\JobseekerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobseekerLanguage>
 */
class JobseekerLanguageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'jobseeker_profile_id' => JobseekerProfile::factory(),
            'language' => fake()->randomElement(Language::cases()),
            'proficiency' => fake()->randomElement(LanguageProficiency::cases()),
            'sort' => 0,
        ];
    }
}
