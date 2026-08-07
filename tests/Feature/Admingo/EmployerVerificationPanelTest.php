<?php

use App\Admingo\Models\StaffUser;
use App\Admingo\Resources\EmployerProfiles\EmployerProfileResource;
use App\Admingo\Resources\EmployerProfiles\Pages\ListEmployerProfiles;
use App\Admingo\Resources\Jobs\JobResource;
use App\Admingo\Resources\Jobs\Pages\ListJobs;
use App\Enums\JobStatus;
use App\Enums\VerificationDecision;
use App\Models\EmployerProfile;
use App\Models\EmployerVerificationEvent;
use App\Models\Job;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Notification::fake();
    Filament::setCurrentPanel('admingo');

    $this->actingAs(StaffUser::factory()->create(), 'admingo');
});

it('lists companies waiting on a decision and hides the verified ones', function () {
    $waiting = EmployerProfile::factory()->verificationRequested()->create();
    $verified = EmployerProfile::factory()->verified()->create();

    livewire(ListEmployerProfiles::class)
        ->assertCanSeeTableRecords([$waiting])
        ->assertCanNotSeeTableRecords([$verified]);
});

it('verifies a company, records who did it, and publishes what was waiting', function () {
    $profile = EmployerProfile::factory()->verificationRequested()->create();
    $ad = Job::factory()->pending()->for($profile, 'employerProfile')->create();

    livewire(ListEmployerProfiles::class)
        ->callAction(TestAction::make('verify')->table($profile), [
            'employer_message' => 'Welcome aboard.',
        ])
        ->assertHasNoErrors();

    expect($profile->refresh()->isVerified())->toBeTrue()
        ->and($ad->refresh()->status)->toBe(JobStatus::Active);

    $event = EmployerVerificationEvent::query()->sole();

    expect($event->decision)->toBe(VerificationDecision::Verified)
        ->and($event->employer_message)->toBe('Welcome aboard.');
});

it('refuses to withdraw verification without an internal reason', function () {
    $profile = EmployerProfile::factory()->verified()->create();

    livewire(ListEmployerProfiles::class)
        ->filterTable('unverified', false)
        ->callAction(TestAction::make('unverify')->table($profile), [
            'reason' => '',
        ])
        ->assertHasActionErrors(['reason' => 'required']);

    expect($profile->refresh()->isVerified())->toBeTrue();
});

it('withdraws verification and takes the live ads down', function () {
    $profile = EmployerProfile::factory()->verified()->create();
    $live = Job::factory()->for($profile, 'employerProfile')->create();

    livewire(ListEmployerProfiles::class)
        ->filterTable('unverified', false)
        ->callAction(TestAction::make('unverify')->table($profile), [
            'reason' => 'Fake listings, ticket #4412',
        ])
        ->assertHasNoErrors();

    expect($profile->refresh()->isVerified())->toBeFalse()
        ->and($live->refresh()->status)->toBe(JobStatus::Pending);

    expect(EmployerVerificationEvent::query()->sole()->reason)
        ->toBe('Fake listings, ticket #4412');
});

it('counts the ads each company has waiting', function () {
    $profile = EmployerProfile::factory()->verificationRequested()->create();
    Job::factory()->pending()->count(3)->for($profile, 'employerProfile')->create();
    Job::factory()->draft()->for($profile, 'employerProfile')->create();

    // The aggregate has to land on the attribute the column reads, or the
    // whole column renders blank and staff cannot see what is queued.
    livewire(ListEmployerProfiles::class)
        ->assertCanSeeTableRecords([$profile])
        ->assertTableColumnStateSet('jobs_pending_count', 3, $profile);
});

it('can still resolve the company it has just verified', function () {
    $profile = EmployerProfile::factory()->verificationRequested()->create();
    Job::factory()->pending()->for($profile, 'employerProfile')->create();

    // Verifying drops the row out of the default "not yet verified" filter, so
    // the follow-up progress modal has to resolve its record without it — the
    // resolution failure is swallowed by Filament, and the modal would simply
    // never open.
    livewire(ListEmployerProfiles::class)
        ->callAction(TestAction::make('verify')->table($profile))
        ->assertActionMounted(TestAction::make('publishProgress')->table($profile));
});

it('badges the companies that actually asked, not every unverified one', function () {
    EmployerProfile::factory()->verificationRequested()->count(2)->create();
    EmployerProfile::factory()->unverified()->create();

    expect(EmployerProfileResource::getNavigationBadge())->toBe('2');
});

it('defaults the job queue to what is waiting to be published', function () {
    $pending = Job::factory()->pending()->create();
    $live = Job::factory()->create();

    livewire(ListJobs::class)
        ->assertCanSeeTableRecords([$pending])
        ->assertCanNotSeeTableRecords([$live]);

    expect(JobResource::getNavigationBadge())->toBe('1');
});

it('never offers staff a way to write to jobs', function () {
    expect(JobResource::canCreate())->toBeFalse()
        ->and(JobResource::canEdit(Job::factory()->create()))->toBeFalse()
        ->and(JobResource::canDelete(Job::factory()->create()))->toBeFalse();
});
