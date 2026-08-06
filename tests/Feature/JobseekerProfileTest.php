<?php

use App\Enums\Availability;
use App\Enums\Currency;
use App\Enums\EducationLevel;
use App\Enums\Language;
use App\Enums\LanguageProficiency;
use App\Enums\SalaryPeriod;
use App\Models\Education;
use App\Models\JobseekerLanguage;
use App\Models\JobseekerProfile;
use App\Models\User;
use App\Models\WorkExperience;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('jobseeker can view the profile form', function () {
    $this->actingAs(User::factory()->jobseeker()->create())
        ->get(route('jobseeker.profile.edit'))
        ->assertOk();
});

test('employer cannot access the jobseeker profile form', function () {
    $this->actingAs(User::factory()->employer()->create())
        ->get(route('jobseeker.profile.edit'))
        ->assertForbidden();
});

test('jobseeker can create a profile with a resume', function () {
    Storage::fake('local');

    $user = User::factory()->jobseeker()->create();

    $response = $this->actingAs($user)->put(route('jobseeker.profile.update'), [
        'full_name' => 'Siti Rahma',
        'current_title' => 'Backend Engineer',
        'skills' => ['PHP', 'Laravel'],
        'experience_years' => 5,
        'country' => 'ID',
        'city' => 'Jakarta',
        'phone' => '+628123456789',
        'resume' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
    ]);

    $response->assertRedirect(route('jobseeker.profile.edit', absolute: false));

    $profile = JobseekerProfile::query()->whereBelongsTo($user)->firstOrFail();

    expect($profile->skills)->toBe(['PHP', 'Laravel'])
        ->and($profile->resume_path)->not->toBeNull();

    Storage::disk('local')->assertExists($profile->resume_path);
});

test('uploading a new resume replaces the old file', function () {
    Storage::fake('local');

    $user = User::factory()->jobseeker()->create();
    $profile = JobseekerProfile::factory()->for($user)->create([
        'resume_path' => UploadedFile::fake()->create('old.pdf', 10)->store('resumes', 'local'),
    ]);

    $oldPath = $profile->resume_path;

    $this->actingAs($user)->put(route('jobseeker.profile.update'), [
        'full_name' => $profile->full_name,
        'current_title' => $profile->current_title,
        'skills' => $profile->skills,
        'experience_years' => $profile->experience_years,
        'country' => $profile->country,
        'city' => $profile->city,
        'resume' => UploadedFile::fake()->create('new.pdf', 100, 'application/pdf'),
    ])->assertRedirect(route('jobseeker.profile.edit', absolute: false));

    Storage::disk('local')->assertMissing($oldPath);
    Storage::disk('local')->assertExists($profile->refresh()->resume_path);
});

test('jobseeker can save preference fields', function () {
    $user = User::factory()->jobseeker()->create();

    $this->actingAs($user)->put(route('jobseeker.profile.update'), [
        'full_name' => 'Siti Rahma',
        'current_title' => 'Backend Engineer',
        'preferred_job_title' => 'Engineering Manager',
        'skills' => ['PHP'],
        'experience_years' => 5,
        'country' => 'ID',
        'city' => 'Jakarta',
        'preferred_country' => 'SG',
        'preferred_city' => 'Singapore',
        'availability' => 'two_weeks',
        'languages' => [
            ['language' => 'id', 'proficiency' => 'native'],
            ['language' => 'en', 'proficiency' => 'fluent'],
        ],
        'gender' => 'female',
        'summary' => 'Ten years shipping Laravel APIs.',
        'current_company' => 'Warung Tech',
        'date_of_birth' => '1995-04-12',
        'expected_salary_min' => 15_000_000,
        'expected_salary_max' => 22_000_000,
        'expected_salary_currency' => 'IDR',
        'expected_salary_period' => 'monthly',
        'educations' => [
            ['institution' => 'Universitas Indonesia', 'field_of_study' => 'Computer Science', 'level' => 'bachelor'],
        ],
    ])->assertRedirect(route('jobseeker.profile.edit', absolute: false));

    $profile = JobseekerProfile::query()->whereBelongsTo($user)->firstOrFail();

    expect($profile->preferred_job_title)->toBe('Engineering Manager')
        ->and($profile->preferred_country)->toBe('SG')
        ->and($profile->availability)->toBe(Availability::TwoWeeks)
        ->and($profile->summary)->toBe('Ten years shipping Laravel APIs.')
        ->and($profile->current_company)->toBe('Warung Tech')
        ->and($profile->expected_salary_min)->toBe(15_000_000)
        ->and($profile->expected_salary_currency)->toBe(Currency::Idr)
        ->and($profile->expected_salary_period)->toBe(SalaryPeriod::Monthly)
        ->and($profile->languages->pluck('language')->all())->toBe([Language::Indonesian, Language::English])
        ->and($profile->languages->pluck('proficiency')->all())->toBe([LanguageProficiency::Native, LanguageProficiency::Fluent])
        ->and($profile->educations->pluck('institution')->all())->toBe(['Universitas Indonesia'])
        ->and($profile->highestEducationLevel())->toBe(EducationLevel::Bachelor);
});

test('age is derived from the birth date rather than stored', function () {
    $profile = JobseekerProfile::factory()->create(['date_of_birth' => now()->subYears(30)->subMonths(2)]);

    expect($profile->age())->toBe(30)
        ->and(JobseekerProfile::factory()->create(['date_of_birth' => null])->age())->toBeNull();
});

test('the highest education level wins across multiple degrees', function () {
    $profile = JobseekerProfile::factory()->create();

    Education::factory()->for($profile, 'jobseekerProfile')->create(['level' => EducationLevel::Bachelor]);
    Education::factory()->for($profile, 'jobseekerProfile')->create(['level' => EducationLevel::HighSchool]);
    Education::factory()->for($profile, 'jobseekerProfile')->create(['level' => null]);

    expect($profile->refresh()->highestEducationLevel())->toBe(EducationLevel::Bachelor);
});

test('educations and languages are created, updated, and deleted from the submitted list', function () {
    $user = User::factory()->jobseeker()->create();
    $profile = JobseekerProfile::factory()->for($user)->create();
    $keptEducation = Education::factory()->for($profile, 'jobseekerProfile')->create(['institution' => 'Sekolah Lama']);
    $removedEducation = Education::factory()->for($profile, 'jobseekerProfile')->create(['institution' => 'Kursus Singkat']);
    $removedLanguage = JobseekerLanguage::factory()->for($profile, 'jobseekerProfile')->create(['language' => Language::Thai]);

    $this->actingAs($user)->put(route('jobseeker.profile.update'), [
        'full_name' => $profile->full_name,
        'current_title' => $profile->current_title,
        'skills' => $profile->skills,
        'experience_years' => $profile->experience_years,
        'country' => $profile->country,
        'city' => $profile->city,
        'educations' => [
            ['id' => $keptEducation->id, 'institution' => 'Universiti Malaya', 'level' => 'master'],
        ],
        'languages' => [
            ['language' => 'en', 'proficiency' => 'good'],
        ],
    ])->assertRedirect(route('jobseeker.profile.edit', absolute: false));

    expect($keptEducation->refresh()->institution)->toBe('Universiti Malaya')
        ->and(Education::query()->find($removedEducation->id))->toBeNull()
        ->and(JobseekerLanguage::query()->find($removedLanguage->id))->toBeNull()
        ->and($profile->refresh()->languages->pluck('language')->all())->toBe([Language::English]);
});

test('a profile still saves without any of the new optional fields', function () {
    $user = User::factory()->jobseeker()->create();

    $this->actingAs($user)->put(route('jobseeker.profile.update'), [
        'full_name' => 'Siti Rahma',
        'current_title' => 'Backend Engineer',
        'skills' => ['PHP'],
        'experience_years' => 5,
        'country' => 'ID',
        'city' => 'Jakarta',
    ])->assertRedirect(route('jobseeker.profile.edit', absolute: false));

    expect(JobseekerProfile::query()->whereBelongsTo($user)->exists())->toBeTrue();
});

test('work experiences are created, updated, and deleted from the submitted list', function () {
    $user = User::factory()->jobseeker()->create();
    $profile = JobseekerProfile::factory()->for($user)->create();
    $kept = WorkExperience::factory()->for($profile, 'jobseekerProfile')->create(['job_title' => 'Junior Cook']);
    $removed = WorkExperience::factory()->for($profile, 'jobseekerProfile')->create(['job_title' => 'Dishwasher']);

    $this->actingAs($user)->put(route('jobseeker.profile.update'), [
        'full_name' => $profile->full_name,
        'current_title' => $profile->current_title,
        'skills' => $profile->skills,
        'experience_years' => $profile->experience_years,
        'country' => $profile->country,
        'city' => $profile->city,
        'experiences' => [
            ['id' => $kept->id, 'job_title' => 'Line Cook', 'company_name' => $kept->company_name, 'start_date' => '2020-01-01', 'end_date' => '2022-06-01'],
            ['id' => null, 'job_title' => 'Sous Chef', 'company_name' => 'Warung Enak', 'start_date' => '2022-07-01', 'end_date' => null],
        ],
    ])->assertRedirect(route('jobseeker.profile.edit', absolute: false));

    $titles = $profile->refresh()->workExperiences->pluck('job_title');

    expect($titles)->toContain('Line Cook')
        ->and($titles)->toContain('Sous Chef')
        ->and($titles)->not->toContain('Dishwasher')
        ->and($kept->refresh()->job_title)->toBe('Line Cook')
        ->and(WorkExperience::query()->find($removed->id))->toBeNull();
});

test("a foreign experience id cannot be adopted into another jobseeker's profile", function () {
    $victim = WorkExperience::factory()->create(['job_title' => 'CFO']);

    $user = User::factory()->jobseeker()->create();
    $profile = JobseekerProfile::factory()->for($user)->create();

    $this->actingAs($user)->put(route('jobseeker.profile.update'), [
        'full_name' => $profile->full_name,
        'current_title' => $profile->current_title,
        'skills' => $profile->skills,
        'experience_years' => $profile->experience_years,
        'country' => $profile->country,
        'city' => $profile->city,
        'experiences' => [
            ['id' => $victim->id, 'job_title' => 'Hijacked', 'company_name' => 'Evil Co', 'start_date' => '2020-01-01', 'end_date' => null],
        ],
    ])->assertRedirect(route('jobseeker.profile.edit', absolute: false));

    // The victim's row is untouched; the submitted row lands as a new record.
    expect($victim->refresh()->job_title)->toBe('CFO')
        ->and($victim->jobseeker_profile_id)->not->toBe($profile->id)
        ->and($profile->refresh()->workExperiences->pluck('job_title'))->toContain('Hijacked');
});

test('profile validation rejects bad input', function (array $overrides, string $errorField) {
    $user = User::factory()->jobseeker()->create();

    $valid = [
        'full_name' => 'Siti Rahma',
        'current_title' => 'Backend Engineer',
        'skills' => ['PHP'],
        'experience_years' => 5,
        'country' => 'ID',
        'city' => 'Jakarta',
    ];

    $this->actingAs($user)
        ->put(route('jobseeker.profile.update'), [...$valid, ...$overrides])
        ->assertSessionHasErrors($errorField);
})->with([
    'empty skills' => [['skills' => []], 'skills'],
    'unknown country' => [['country' => 'US'], 'country'],
    'invalid phone' => [['phone' => 'not-a-phone'], 'phone'],
    'negative experience' => [['experience_years' => -1], 'experience_years'],
    'unknown availability' => [['availability' => 'someday'], 'availability'],
    'unknown language' => [['languages' => [['language' => 'fr', 'proficiency' => 'good']]], 'languages.0.language'],
    'duplicate language' => [
        ['languages' => [
            ['language' => 'en', 'proficiency' => 'good'],
            ['language' => 'en', 'proficiency' => 'native'],
        ]],
        'languages.1.language',
    ],
    'unknown proficiency' => [['languages' => [['language' => 'en', 'proficiency' => 'perfect']]], 'languages.0.proficiency'],
    'birth date too recent' => [['date_of_birth' => now()->subYears(10)->toDateString()], 'date_of_birth'],
    'salary max below min' => [
        ['expected_salary_min' => 20_000_000, 'expected_salary_max' => 5_000_000, 'expected_salary_currency' => 'IDR'],
        'expected_salary_max',
    ],
    'salary without a currency' => [['expected_salary_min' => 20_000_000], 'expected_salary_currency'],
    'education missing institution' => [
        ['educations' => [['institution' => '', 'level' => 'bachelor']]],
        'educations.0.institution',
    ],
    'experience missing title' => [
        ['experiences' => [['job_title' => '', 'company_name' => 'Co', 'start_date' => '2020-01-01']]],
        'experiences.0.job_title',
    ],
    'experience ends before it starts' => [
        ['experiences' => [['job_title' => 'Cook', 'company_name' => 'Co', 'start_date' => '2022-01-01', 'end_date' => '2020-01-01']]],
        'experiences.0.end_date',
    ],
    'experience starts in the future' => [
        ['experiences' => [['job_title' => 'Cook', 'company_name' => 'Co', 'start_date' => '2090-01-01']]],
        'experiences.0.start_date',
    ],
]);
