<?php

namespace App\Models;

use App\Enums\Availability;
use App\Enums\Country;
use App\Enums\EducationLevel;
use App\Enums\Gender;
use Database\Factories\JobseekerProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;

/**
 * @property string $id
 * @property string $user_id
 * @property string $full_name
 * @property string $current_title
 * @property string|null $preferred_job_title
 * @property array<int, string> $skills
 * @property int $experience_years
 * @property string $country
 * @property string $city
 * @property string|null $preferred_country
 * @property string|null $preferred_city
 * @property Availability|null $availability
 * @property array<int, string>|null $languages
 * @property Gender|null $gender
 * @property EducationLevel|null $education_level
 * @property string|null $phone
 * @property string|null $resume_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Collection<int, Application> $applications
 * @property-read Collection<int, WorkExperience> $workExperiences
 */
#[Fillable(['full_name', 'current_title', 'preferred_job_title', 'skills', 'experience_years', 'country', 'city', 'preferred_country', 'preferred_city', 'availability', 'languages', 'gender', 'education_level', 'phone', 'resume_path'])]
class JobseekerProfile extends Model
{
    /** @use HasFactory<JobseekerProfileFactory> */
    use HasFactory, HasUlids, Searchable;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * @return HasMany<WorkExperience, $this>
     */
    public function workExperiences(): HasMany
    {
        return $this->hasMany(WorkExperience::class)
            ->orderBy('sort')
            ->orderByDesc('start_date');
    }

    /**
     * Every profile is discoverable for now. A future jobseeker opt-out
     * (consent flag) plugs in here; employer-side quota gating lives in the
     * controller/policy layer, not the index.
     */
    public function shouldBeSearchable(): bool
    {
        return true;
    }

    /**
     * The five text fields feeding the Typesense `embedding` (see
     * config/scout.php) must ALWAYS be present — Typesense fails to embed a
     * document with a missing source field — so they fall back to
     * current-role data instead of being null-filtered. Only the optional
     * facet fields are filtered when null. The `embedding` field itself is
     * generated server-side and must not be sent here.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $this->loadMissing('workExperiences');

        $currentLocation = $this->formatLocation($this->city, $this->country);
        $preferredLocation = $this->formatLocation($this->preferred_city, $this->preferred_country);

        $experienceTitles = $this->workExperiences->pluck('job_title')->all();

        return [
            'id' => (string) $this->id,
            'full_name' => $this->full_name,
            'preferred_job_title' => $this->preferred_job_title ?? $this->current_title,
            'experience_titles' => $experienceTitles === [] ? [$this->current_title] : $experienceTitles,
            'skills' => $this->skills,
            'preferred_location' => $preferredLocation ?? $currentLocation,
            'location' => $currentLocation,
            'experience_years' => $this->experience_years,
            'experience_band' => $this->experienceBand(),
            'country' => $this->country,
            'city' => $this->city,
            ...array_filter([
                'preferred_country' => $this->preferred_country,
                'preferred_city' => $this->preferred_city,
                'availability' => $this->availability?->value,
                'gender' => $this->gender?->value,
                'education_level' => $this->education_level?->value,
                'languages' => $this->languages,
            ], fn (mixed $value): bool => $value !== null),
            'created_at' => $this->created_at?->getTimestamp() ?? 0,
        ];
    }

    /**
     * Bucket experience years into the facet bands the search UI offers.
     */
    public function experienceBand(): string
    {
        return match (true) {
            $this->experience_years <= 1 => '0-1',
            $this->experience_years <= 4 => '2-4',
            $this->experience_years <= 9 => '5-9',
            default => '10+',
        };
    }

    private function formatLocation(?string $city, ?string $country): ?string
    {
        $parts = array_filter([$city, $country === null ? null : Country::tryFrom($country)?->label()]);

        return $parts === [] ? null : implode(', ', $parts);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'experience_years' => 'integer',
            'availability' => Availability::class,
            'languages' => 'array',
            'gender' => Gender::class,
            'education_level' => EducationLevel::class,
        ];
    }
}
