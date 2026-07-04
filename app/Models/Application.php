<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $job_id
 * @property string $jobseeker_profile_id
 * @property ApplicationStatus $status
 * @property string|null $resume_path
 * @property string|null $cover_note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Job $job
 * @property-read JobseekerProfile $jobseekerProfile
 */
#[Fillable(['status', 'resume_path', 'cover_note'])]
class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory, HasUlids;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'submitted',
    ];

    /**
     * @return BelongsTo<Job, $this>
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

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
            'status' => ApplicationStatus::class,
        ];
    }
}
