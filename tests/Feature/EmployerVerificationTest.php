<?php

use App\Actions\Jobs\PublishJob;
use App\Actions\Verification\RequestVerification;
use App\Actions\Verification\UnverifyEmployer;
use App\Actions\Verification\VerifyEmployer;
use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Enums\VerificationDecision;
use App\Jobs\PublishPendingJob;
use App\Models\EmployerProfile;
use App\Models\EmployerVerificationEvent;
use App\Models\Job;
use App\Models\User;
use App\Notifications\EmployerUnverified;
use App\Notifications\EmployerVerified;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();

    $this->staff = User::factory()->create(['role' => UserRole::Staff]);
});

it('parks a job rather than publishing it when the employer may not publish', function () {
    $profile = EmployerProfile::factory()->unverified()->create();
    $job = Job::factory()->draft()->for($profile, 'employerProfile')->create();

    app(PublishJob::class)->handle($job);

    expect($job->refresh()->status)->toBe(JobStatus::Pending)
        ->and($job->published_at)->toBeNull()
        // The clock must not start while the ad is invisible, or a company
        // verified three weeks later would be sold 45 days and get 24.
        ->and($job->expires_at)->toBeNull();
});

it('clears a lapsed window when it parks an ad, so the expiry sweep leaves it alone', function () {
    $profile = EmployerProfile::factory()->unverified()->create();
    // Re-publishing an expired ad is a supported flow — the jobs list offers
    // it as "Re-publish". Parked with its old expiry still stamped, jobs:expire
    // would take it straight back to Expired, out of the Pending queue
    // VerifyEmployer publishes from, after the employer was told it was waiting.
    $job = Job::factory()->expired()->for($profile, 'employerProfile')->create();

    app(PublishJob::class)->handle($job);

    expect($job->refresh()->status)->toBe(JobStatus::Pending)
        ->and($job->expires_at)->toBeNull();

    $this->artisan('jobs:expire')->assertSuccessful();

    expect($job->refresh()->status)->toBe(JobStatus::Pending);

    app(VerifyEmployer::class)->handle($profile, $this->staff);

    expect($job->refresh()->status)->toBe(JobStatus::Active)
        ->and($job->expires_at?->isFuture())->toBeTrue();
});

it('keeps a running window when it parks an ad', function () {
    $profile = EmployerProfile::factory()->verified()->create();
    $job = Job::factory()->for($profile, 'employerProfile')->create();
    $expiresAt = $job->expires_at;

    app(UnverifyEmployer::class)->handle($profile, 'Parked mid-window', $this->staff);
    // Re-submitting a parked ad must not restamp or drop the days it has left.
    app(PublishJob::class)->handle($job->refresh());

    expect($job->refresh()->status)->toBe(JobStatus::Pending)
        ->and($job->expires_at?->eq($expiresAt))->toBeTrue();
});

it('publishes normally when the employer may publish', function () {
    $profile = EmployerProfile::factory()->verified()->create();
    $job = Job::factory()->draft()->for($profile, 'employerProfile')->create();

    app(PublishJob::class)->handle($job);

    expect($job->refresh()->status)->toBe(JobStatus::Active)
        ->and($job->expires_at?->isFuture())->toBeTrue();
});

it('publishes every parked job when the company is verified', function () {
    $profile = EmployerProfile::factory()->unverified()->create();
    $parked = Job::factory()->pending()->count(3)->for($profile, 'employerProfile')->create();
    $draft = Job::factory()->draft()->for($profile, 'employerProfile')->create();
    $closed = Job::factory()->closed()->for($profile, 'employerProfile')->create();

    app(VerifyEmployer::class)->handle($profile, $this->staff);

    $parked->each(function (Job $job) {
        expect($job->refresh()->status)->toBe(JobStatus::Active)
            ->and($job->expires_at?->isFuture())->toBeTrue();
    });

    // Only what was actually waiting on the gate goes live. A draft was never
    // submitted and a closed ad was taken down on purpose.
    expect($draft->refresh()->status)->toBe(JobStatus::Draft)
        ->and($closed->refresh()->status)->toBe(JobStatus::Closed);
});

it('stamps the clock at verification, not at the moment the ad was written', function () {
    $profile = EmployerProfile::factory()->unverified()->create();
    $job = Job::factory()->pending()->for($profile, 'employerProfile')->create([
        'created_at' => now()->subDays(20),
    ]);

    app(VerifyEmployer::class)->handle($profile, $this->staff);

    expect(now()->diffInDays($job->refresh()->expires_at))
        ->toEqualWithDelta(Job::PUBLISH_WINDOW_DAYS, 1);
});

it('records the verification, its actor and the batch it started', function () {
    $profile = EmployerProfile::factory()->verificationRequested()->create();
    Job::factory()->pending()->for($profile, 'employerProfile')->create();

    app(VerifyEmployer::class)->handle($profile, $this->staff, employerMessage: 'Welcome aboard.');

    $profile->refresh();

    expect($profile->isVerified())->toBeTrue()
        ->and($profile->verified_by_id)->toBe($this->staff->id)
        // The request has been answered; leaving it set would keep the company
        // in the queue and keep aging its waiting time.
        ->and($profile->verification_requested_at)->toBeNull()
        ->and($profile->publish_batch_id)->not->toBeNull();

    $event = EmployerVerificationEvent::query()->sole();

    expect($event->decision)->toBe(VerificationDecision::Verified)
        ->and($event->actor_id)->toBe($this->staff->id)
        ->and($event->employer_message)->toBe('Welcome aboard.')
        ->and($event->reason)->toBeNull();

    Notification::assertSentTo($profile->user, EmployerVerified::class);
});

it('does nothing when the company is already verified', function () {
    $profile = EmployerProfile::factory()->verified()->create();

    app(VerifyEmployer::class)->handle($profile, $this->staff);

    expect(EmployerVerificationEvent::query()->count())->toBe(0);

    Notification::assertNothingSent();
});

it('pulls live ads back down when verification is withdrawn', function () {
    $profile = EmployerProfile::factory()->verified()->create();
    $live = Job::factory()->for($profile, 'employerProfile')->create();
    $draft = Job::factory()->draft()->for($profile, 'employerProfile')->create();

    $expiresAt = $live->expires_at;

    app(UnverifyEmployer::class)->handle($profile, 'Fake listings, ticket #4412', $this->staff);

    expect($profile->refresh()->isVerified())->toBeFalse()
        ->and($live->refresh()->status)->toBe(JobStatus::Pending)
        // Neither refilled nor frozen: the ad comes back on its remaining days.
        ->and($live->expires_at?->eq($expiresAt))->toBeTrue()
        ->and($draft->refresh()->status)->toBe(JobStatus::Draft);
});

it('stops a running publish batch when verification is withdrawn', function () {
    $profile = EmployerProfile::factory()->unverified()->create();
    Job::factory()->pending()->for($profile, 'employerProfile')->create();

    app(VerifyEmployer::class)->handle($profile, $this->staff);

    expect($profile->refresh()->publish_batch_id)->not->toBeNull();

    app(UnverifyEmployer::class)->handle($profile, 'Revoked mid-run', $this->staff);

    expect($profile->refresh()->publish_batch_id)->toBeNull();
});

it('publishes nothing once its batch has been cancelled', function () {
    $profile = EmployerProfile::factory()->unverified()->create();
    $ad = Job::factory()->pending()->for($profile, 'employerProfile')->create();

    [$queuedJob] = (new PublishPendingJob($ad))->withFakeBatch(cancelledAt: CarbonImmutable::now());

    $queuedJob->handle(app(PublishJob::class));

    expect($ad->refresh()->status)->toBe(JobStatus::Pending);
});

it('leaves an ad alone if it moved out of pending before the batch reached it', function () {
    $profile = EmployerProfile::factory()->verified()->create();
    $ad = Job::factory()->draft()->for($profile, 'employerProfile')->create();

    [$queuedJob] = (new PublishPendingJob($ad))->withFakeBatch();

    $queuedJob->handle(app(PublishJob::class));

    expect($ad->refresh()->status)->toBe(JobStatus::Draft);
});

it('keeps the internal reason out of the employer notification', function () {
    $profile = EmployerProfile::factory()->verified()->create();

    app(UnverifyEmployer::class)->handle(
        $profile,
        'Suspected fraud, see ticket #4412',
        $this->staff,
        employerMessage: 'We need updated company documents.',
    );

    $event = EmployerVerificationEvent::query()->sole();

    expect($event->decision)->toBe(VerificationDecision::Unverified)
        ->and($event->reason)->toBe('Suspected fraud, see ticket #4412')
        ->and($event->employer_message)->toBe('We need updated company documents.');

    Notification::assertSentTo(
        $profile->user,
        EmployerUnverified::class,
        fn (EmployerUnverified $notification) => $notification->employerMessage === 'We need updated company documents.',
    );
});

it('does nothing when the company is already unverified', function () {
    $profile = EmployerProfile::factory()->unverified()->create();

    app(UnverifyEmployer::class)->handle($profile, 'Nothing to revoke', $this->staff);

    expect(EmployerVerificationEvent::query()->count())->toBe(0);
});

it('parks every live ad, not just the first chunk', function () {
    $profile = EmployerProfile::factory()->verified()->create();
    // Two chunks' worth at the 200 chunk size. An offset-based chunk would step
    // past everything the first page shifted out of the result set, leaving the
    // rest live and indexed.
    Job::factory()->count(205)->for($profile, 'employerProfile')->create();

    app(UnverifyEmployer::class)->handle($profile, 'Bulk revocation', $this->staff);

    expect($profile->jobs()->where('status', JobStatus::Active)->count())->toBe(0)
        ->and($profile->jobs()->where('status', JobStatus::Pending)->count())->toBe(205);
});

it('cancels the batch using the id stored at revocation time', function () {
    $profile = EmployerProfile::factory()->unverified()->create();
    Job::factory()->pending()->for($profile, 'employerProfile')->create();

    app(VerifyEmployer::class)->handle($profile, $this->staff);

    // A caller holding a copy hydrated before the verification: the batch id it
    // knows about is null, so cancelling has to read the stored one instead.
    $stale = EmployerProfile::query()->findOrFail($profile->id);
    $stale->forceFill(['publish_batch_id' => null])->syncOriginal();

    app(UnverifyEmployer::class)->handle($stale, 'Revoked from a stale copy', $this->staff);

    expect($profile->refresh()->publish_batch_id)->toBeNull()
        ->and($profile->isVerified())->toBeFalse();
});

it('refuses to withdraw verification on a blank reason', function () {
    $profile = EmployerProfile::factory()->verified()->create();

    // `required` is satisfied by spaces, so the invariant cannot live in the
    // form alone: an audit row with no reason records nothing worth keeping.
    expect(fn () => app(UnverifyEmployer::class)->handle($profile, '   ', $this->staff))
        ->toThrow(InvalidArgumentException::class);

    expect($profile->refresh()->isVerified())->toBeTrue()
        ->and(EmployerVerificationEvent::query()->count())->toBe(0);
});

it('pulls ads down outside the transaction that revokes', function () {
    $profile = EmployerProfile::factory()->verified()->create();
    $live = Job::factory()->for($profile, 'employerProfile')->create();

    // Scout indexes synchronously here, so parking inside the revocation would
    // hold the profile's row lock across a blocking call to the search engine.
    // Observed indirectly: the revocation is already committed by the time the
    // ads move, so a listener on the job save sees no open transaction.
    // Compared against the baseline rather than zero: RefreshDatabase already
    // holds one transaction open around every test.
    $baseline = DB::transactionLevel();
    $depthWhenParked = null;

    Job::saved(function () use (&$depthWhenParked): void {
        $depthWhenParked ??= DB::transactionLevel();
    });

    app(UnverifyEmployer::class)->handle($profile, 'Parked outside the lock', $this->staff);

    expect($depthWhenParked)->toBe($baseline)
        ->and($live->refresh()->status)->toBe(JobStatus::Pending);
});

it('records a verification request once, without restarting the clock', function () {
    $profile = EmployerProfile::factory()->unverified()->create();

    app(RequestVerification::class)->handle($profile);
    $requestedAt = $profile->refresh()->verification_requested_at;

    $this->travel(1)->days();
    app(RequestVerification::class)->handle($profile);

    expect($profile->refresh()->verification_requested_at?->eq($requestedAt))->toBeTrue();
});

it('ignores a verification request from a company that is already verified', function () {
    $profile = EmployerProfile::factory()->verified()->create();

    app(RequestVerification::class)->handle($profile);

    expect($profile->refresh()->verification_requested_at)->toBeNull();
});
