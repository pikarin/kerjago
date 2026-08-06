<?php

namespace App\Models;

use App\Enums\EducationLevel;
use Database\Factories\EducationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $jobseeker_profile_id
 * @property string $institution
 * @property string|null $field_of_study
 * @property EducationLevel|null $level
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property int $sort
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read JobseekerProfile $jobseekerProfile
 */
#[Fillable(['institution', 'field_of_study', 'level', 'start_date', 'end_date', 'sort'])]
class Education extends Model
{
    /** @use HasFactory<EducationFactory> */
    use HasFactory, HasUlids;

    /**
     * "Education" is uncountable to Laravel's inflector, which would resolve
     * the table to `education`.
     *
     * @var string
     */
    protected $table = 'educations';

    /**
     * Touching the parent profile fires its saved event, which re-syncs the
     * profile's search document whenever an education row changes.
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
            'level' => EducationLevel::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'sort' => 'integer',
        ];
    }
}
