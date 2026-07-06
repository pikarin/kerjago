<?php

namespace App\Models;

use Database\Factories\WorkExperienceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $jobseeker_profile_id
 * @property string $job_title
 * @property string $company_name
 * @property Carbon $start_date
 * @property Carbon|null $end_date
 * @property int $sort
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read JobseekerProfile $jobseekerProfile
 */
#[Fillable(['job_title', 'company_name', 'start_date', 'end_date', 'sort'])]
class WorkExperience extends Model
{
    /** @use HasFactory<WorkExperienceFactory> */
    use HasFactory, HasUlids;

    /**
     * Touching the parent profile fires its saved event, which re-syncs the
     * profile's search document whenever an experience row changes.
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
            'start_date' => 'date',
            'end_date' => 'date',
            'sort' => 'integer',
        ];
    }
}
