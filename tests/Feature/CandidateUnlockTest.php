<?php

use App\Actions\Applications\ApplyToJob;
use App\Actions\Chat\EnsureApplicationConversation;
use App\Actions\Jobs\PublishJob;
use App\Actions\Unlocks\IssueCandidateUnlock;
use App\Chat\Models\Conversation;
use App\Enums\UnlockSource;
use App\Models\CandidateUnlock;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\JobseekerProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * @return list<JobseekerProfile>
 */
function applicants(int $count): array
{
    return JobseekerProfile::factory()->count($count)->create()->all();
}

test('the first ten applicants are unlocked and the eleventh is not', function () {
    $job = Job::factory()->create();

    foreach (applicants(12) as $profile) {
        app(ApplyToJob::class)->handle($profile, $job->fresh());
    }

    expect(CandidateUnlock::query()->where('job_id', $job->id)->count())->toBe(10);

    $unlockedProfileIds = CandidateUnlock::query()->pluck('jobseeker_profile_id')->all();
    $applicantIdsInOrder = $job->applications()->orderBy('created_at')->orderBy('id')->pluck('jobseeker_profile_id')->all();

    expect(array_slice($applicantIdsInOrder, 0, 10))
        ->each->toBeIn($unlockedProfileIds)
        ->and(array_slice($applicantIdsInOrder, 10))
        ->each->not->toBeIn($unlockedProfileIds);
});

test('an unlock runs for a year past the job\'s expiry', function () {
    $job = app(PublishJob::class)->handle(Job::factory()->draft()->create());
    $profile = JobseekerProfile::factory()->create();

    app(ApplyToJob::class)->handle($profile, $job);

    $unlock = CandidateUnlock::query()->firstOrFail();

    expect($unlock->expires_at->toDateString())
        ->toBe($job->expires_at?->addYear()->toDateString())
        ->and($unlock->source)->toBe(UnlockSource::AutoFirstTen);
});

test('closing a job early does not shorten unlocks already issued', function () {
    $job = Job::factory()->create();
    app(ApplyToJob::class)->handle(JobseekerProfile::factory()->create(), $job);

    $before = CandidateUnlock::query()->firstOrFail()->expires_at;

    $job->update(['status' => 'closed']);

    expect(CandidateUnlock::query()->firstOrFail()->expires_at->toIso8601String())
        ->toBe($before->toIso8601String());
});

test('re-publishing an expired job does not refill the quota', function () {
    $job = Job::factory()->create();

    foreach (applicants(10) as $profile) {
        app(ApplyToJob::class)->handle($profile, $job->fresh());
    }

    $job->update(['status' => 'expired', 'expires_at' => now()->subDay()]);
    app(PublishJob::class)->handle($job);

    foreach (applicants(3) as $profile) {
        app(ApplyToJob::class)->handle($profile, $job->fresh());
    }

    expect(CandidateUnlock::query()->count())->toBe(10)
        ->and($job->applications()->count())->toBe(13);
});

test('an applicant already unlocked elsewhere still spends a slot', function () {
    $employer = EmployerProfile::factory()->create();
    $firstJob = Job::factory()->for($employer)->create();
    $secondJob = Job::factory()->for($employer)->create();

    $repeatApplicant = JobseekerProfile::factory()->create();
    app(ApplyToJob::class)->handle($repeatApplicant, $firstJob);

    // Applies to the second job first, taking slot 1 there even though the
    // employer can already see them.
    app(ApplyToJob::class)->handle($repeatApplicant, $secondJob->fresh());

    foreach (applicants(10) as $profile) {
        app(ApplyToJob::class)->handle($profile, $secondJob->fresh());
    }

    // Ten applicants after the repeat, but only nine slots were left.
    expect(CandidateUnlock::query()->where('employer_profile_id', $employer->id)->count())->toBe(10);
});

test('a second unlock for the same pair extends rather than duplicates', function () {
    $employer = EmployerProfile::factory()->create();
    $shortJob = Job::factory()->for($employer)->create(['expires_at' => now()->addDays(5)]);
    $longJob = Job::factory()->for($employer)->create(['expires_at' => now()->addDays(90)]);

    $profile = JobseekerProfile::factory()->create();

    app(ApplyToJob::class)->handle($profile, $shortJob);
    app(ApplyToJob::class)->handle($profile, $longJob);

    $unlocks = CandidateUnlock::query()->get();

    expect($unlocks)->toHaveCount(1)
        ->and($unlocks->first()?->expires_at->toDateString())
        ->toBe($longJob->expires_at?->addYear()->toDateString());
});

test('a withdrawn application neither frees its slot nor revokes the unlock', function () {
    $job = Job::factory()->create();
    $profile = JobseekerProfile::factory()->create();

    $application = app(ApplyToJob::class)->handle($profile, $job);
    $application->delete();

    expect(CandidateUnlock::query()->count())->toBe(1);
});

test('applying to an expired job is refused and issues no unlock', function () {
    $job = Job::factory()->expired()->create();

    expect(fn () => app(ApplyToJob::class)->handle(JobseekerProfile::factory()->create(), $job))
        ->toThrow(ValidationException::class);

    expect(CandidateUnlock::query()->count())->toBe(0);
});

test('a renewed unlock clears the revocation so the next expiry is swept again', function () {
    $employer = EmployerProfile::factory()->create();
    $profile = JobseekerProfile::factory()->create();

    CandidateUnlock::factory()->expired()->create([
        'employer_profile_id' => $employer->id,
        'jobseeker_profile_id' => $profile->id,
    ]);

    $this->artisan('unlocks:expire')->assertSuccessful();
    expect(CandidateUnlock::query()->firstOrFail()->revoked_at)->not->toBeNull();

    app(IssueCandidateUnlock::class)->handle($employer, $profile, now()->addYear());

    expect(CandidateUnlock::query()->firstOrFail()->revoked_at)->toBeNull();
});

/**
 * The sweep reads a chunk, then acts on each row. An unlock renewed in that gap
 * must be left alone: revoking it would lock the employer out of threads they
 * hold a live unlock for, and stamping revoked_at would make the sweep skip the
 * row forever when the new term ended.
 */
test('unlocks:expire leaves alone a row renewed since the chunk was read', function () {
    $employer = EmployerProfile::factory()->create();
    $profile = JobseekerProfile::factory()->create();

    $unlock = CandidateUnlock::factory()->expired()->create([
        'employer_profile_id' => $employer->id,
        'jobseeker_profile_id' => $profile->id,
    ]);

    // Stands in for the renewal landing mid-run: the in-memory row the sweep
    // read still looks expired, the database row no longer is.
    CandidateUnlock::query()->whereKey($unlock->id)->update(['expires_at' => now()->addYear()]);

    $this->artisan('unlocks:expire')->assertSuccessful();

    $unlock->refresh();

    expect($unlock->revoked_at)->toBeNull()
        ->and(CandidateUnlock::query()->active()->count())->toBe(1);
});

/**
 * Claiming the row was not enough on its own: a renewal landing between the
 * claim and the revoke would restore the employer and then have it undone a
 * statement later, leaving an active unlock with no thread access, no locked
 * teaser, and nothing to heal it. The sweep now holds the unlock row's lock
 * across both, so the two take turns.
 */
test('a swept unlock never ends up active but locked out of its threads', function () {
    $job = Job::factory()->create();
    $profile = JobseekerProfile::factory()->create();

    app(ApplyToJob::class)->handle($profile, $job);
    app(EnsureApplicationConversation::class)->handle($job->applications()->firstOrFail());

    CandidateUnlock::query()->update(['expires_at' => now()->subDay()]);
    $this->artisan('unlocks:expire')->assertSuccessful();

    $employerUserId = $job->employerProfile->user_id;
    $conversation = Conversation::query()->firstOrFail();

    expect($conversation->hasParticipant($employerUserId))->toBeFalse();

    // Renewing has to put both halves back: the unlock and the access.
    app(IssueCandidateUnlock::class)->handle($job->employerProfile, $profile, now()->addYear());

    $unlock = CandidateUnlock::query()->firstOrFail();

    expect($unlock->revoked_at)->toBeNull()
        ->and($unlock->expires_at->isFuture())->toBeTrue()
        ->and($conversation->fresh()?->hasParticipant($employerUserId))->toBeTrue();
});

test('a renewal no longer than the current term still lifts a revocation', function () {
    $employer = EmployerProfile::factory()->create();
    $profile = JobseekerProfile::factory()->create();

    // The state the sweep-versus-renewal race produces: revoked, yet live.
    CandidateUnlock::factory()->create([
        'employer_profile_id' => $employer->id,
        'jobseeker_profile_id' => $profile->id,
        'expires_at' => now()->addYear(),
        'revoked_at' => now(),
    ]);

    app(IssueCandidateUnlock::class)->handle($employer, $profile, now()->addMonth());

    $unlock = CandidateUnlock::query()->firstOrFail();

    expect($unlock->revoked_at)->toBeNull()
        // Shorter term declined: longest still wins.
        ->and(now()->diffInDays($unlock->expires_at))->toBeGreaterThan(300);
});

test('unlocks:expire does not reprocess a row it has already swept', function () {
    $employer = EmployerProfile::factory()->create();
    $profile = JobseekerProfile::factory()->create();

    CandidateUnlock::factory()->expired()->create([
        'employer_profile_id' => $employer->id,
        'jobseeker_profile_id' => $profile->id,
    ]);

    $this->artisan('unlocks:expire')->assertSuccessful();

    $queries = 0;
    DB::listen(function () use (&$queries) {
        $queries++;
    });

    $this->artisan('unlocks:expire')->assertSuccessful();

    // One SELECT to find nothing to do, not three per historical row.
    expect($queries)->toBeLessThanOrEqual(2);
});

test('unlocks:expire leaves the rows in place so slots stay spent', function () {
    $job = Job::factory()->create();
    app(ApplyToJob::class)->handle(JobseekerProfile::factory()->create(), $job);

    CandidateUnlock::query()->update(['expires_at' => now()->subDay()]);

    $this->artisan('unlocks:expire')->assertSuccessful();

    expect(CandidateUnlock::query()->count())->toBe(1)
        ->and(CandidateUnlock::query()->active()->count())->toBe(0);
});
