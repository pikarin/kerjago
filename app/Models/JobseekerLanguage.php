<?php

namespace App\Models;

use App\Enums\Language;
use App\Enums\LanguageProficiency;
use Database\Factories\JobseekerLanguageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $jobseeker_profile_id
 * @property Language $language
 * @property LanguageProficiency $proficiency
 * @property int $sort
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read JobseekerProfile $jobseekerProfile
 */
#[Fillable(['language', 'proficiency', 'sort'])]
class JobseekerLanguage extends Model
{
    /** @use HasFactory<JobseekerLanguageFactory> */
    use HasFactory, HasUlids;

    /**
     * Touching the parent profile fires its saved event, which re-syncs the
     * profile's search document whenever a language row changes.
     *
     * @var list<string>
     */
    protected $touches = ['jobseekerProfile'];

    /**
     * @return BelongsTo<JobseekerProfile, $this>
     */
    public function jobseekerProfile(): BelongsTo
    {
        return $this->belongsTo(JobseekerProfile::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'language' => Language::class,
            'proficiency' => LanguageProficiency::class,
            'sort' => 'integer',
        ];
    }
}
