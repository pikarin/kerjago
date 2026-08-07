<?php

use App\Chat\Models\Conversation;
use App\Chat\Models\Participant;
use App\Enums\ConversationKind;
use App\Enums\JobStatus;
use App\Enums\VerificationDecision;
use App\Enums\VerificationSource;
use App\Models\EmployerProfile;
use App\Models\EmployerVerificationEvent;
use App\Models\Job;
use App\Models\JobseekerProfile;

/**
 * The gates, asserted on purpose.
 *
 * The employer factory is verified by default so that tests about other things
 * stay about those things — which means the denial paths get no incidental
 * coverage at all. These are the tests that fail if a gate breaks open.
 */
test('an unverified employer sees one page of candidates, with no depth', function () {
    JobseekerProfile::factory(20)->create();

    $this->actingAs(EmployerProfile::factory()->unverified()->create()->user)
        ->get(route('employer.talent.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('employer/talent/Index')
            ->has('profiles.data', 12)
            // Not 20. The engine's count is the pool's depth, and it would
            // reach the client whether or not the UI printed it.
            ->where('profiles.total', 12)
            ->where('profiles.last_page', 1)
            ->where('browseInFull.allowed', false)
            ->where('browseInFull.reason', 'verification_required')
            ->where('facetsAvailable', false)
        );
});

test('a verified employer pages through the whole pool', function () {
    JobseekerProfile::factory(20)->create();

    $this->actingAs(EmployerProfile::factory()->verified()->create()->user)
        ->get(route('employer.talent.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('profiles.total', 20)
            ->where('browseInFull.allowed', true)
        );
});

test('an unverified employer cannot walk the pool a page at a time', function () {
    JobseekerProfile::factory(20)->create();

    $this->actingAs(EmployerProfile::factory()->unverified()->create()->user)
        ->get(route('employer.talent.index', ['page' => 2]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('profiles.data', 12)
            ->where('profiles.current_page', 1)
        );
});

test('an unverified employer still opens a candidate profile', function () {
    $candidate = JobseekerProfile::factory()->create();

    $this->actingAs(EmployerProfile::factory()->unverified()->create()->user)
        ->get(route('employer.talent.show', $candidate))
        ->assertOk();
});

test('publishing a job while unverified parks it instead', function () {
    $profile = EmployerProfile::factory()->unverified()->create();
    $job = Job::factory()->draft()->for($profile, 'employerProfile')->create();

    $this->actingAs($profile->user)
        ->post(route('employer.jobs.publish', $job))
        ->assertRedirect(route('employer.jobs.index', absolute: false));

    expect($job->refresh()->status)->toBe(JobStatus::Pending)
        ->and($job->expires_at)->toBeNull();
});

test('a parked job is invisible to jobseekers', function () {
    $job = Job::factory()->pending()->create();

    $this->get(route('jobs.show', $job))->assertNotFound();
});

test('an unverified employer cannot write in an application thread', function () {
    [$employer, $conversation] = employerConversation(ConversationKind::Application);

    $this->actingAs($employer->user)
        ->post(route('chat.messages.store', $conversation), ['body' => 'Hello'])
        ->assertForbidden();
});

test('an unverified employer can still read the thread', function () {
    [$employer, $conversation] = employerConversation(ConversationKind::Application);

    $this->actingAs($employer->user)
        ->get(route('chat.show', $conversation))
        ->assertOk();
});

test('an unverified employer can still reach support', function () {
    [$employer, $conversation] = employerConversation(ConversationKind::Internal);

    $this->actingAs($employer->user)
        ->post(route('chat.messages.store', $conversation), ['body' => 'Why am I not verified?'])
        ->assertRedirect();
});

test('a verified employer writes wherever they are a participant', function () {
    [$employer, $conversation] = employerConversation(ConversationKind::Application, verified: true);

    $this->actingAs($employer->user)
        ->post(route('chat.messages.store', $conversation), ['body' => 'Hello'])
        ->assertRedirect();
});

test('an unverified employer cannot cold-outreach a candidate', function () {
    $candidate = JobseekerProfile::factory()->create();

    $this->actingAs(EmployerProfile::factory()->unverified()->create()->user)
        ->post(route('employer.talent.chat', $candidate))
        ->assertForbidden();
});

test('renaming a verified company sends it back for review', function () {
    $profile = EmployerProfile::factory()->verified()->create([
        'company_name' => 'Throwaway Co',
        'industry' => 'Technology',
        'country' => 'ID',
        'city' => 'Jakarta',
        'website' => 'https://throwaway.test',
    ]);
    $live = Job::factory()->for($profile, 'employerProfile')->create();

    $this->actingAs($profile->user)
        ->put(route('employer.profile.update'), [
            'company_name' => 'Gojek',
            'industry' => 'Technology',
            'country' => 'ID',
            'city' => 'Jakarta',
            'website' => 'https://throwaway.test',
        ])
        ->assertRedirect();

    // Otherwise the cheapest attack on the whole gate is to get a throwaway
    // company verified and then become someone well known.
    expect($profile->refresh()->isVerified())->toBeFalse()
        ->and($profile->company_name)->toBe('Gojek')
        ->and($live->refresh()->status)->toBe(JobStatus::Pending);

    expect(EmployerVerificationEvent::query()->sole())
        ->decision->toBe(VerificationDecision::Unverified)
        ->source->toBe(VerificationSource::System);
});

test('editing details that do not identify the company keeps verification', function () {
    $profile = EmployerProfile::factory()->verified()->create([
        'company_name' => 'Kerjago Labs',
        'industry' => 'Technology',
        'country' => 'ID',
        'city' => 'Jakarta',
        'website' => 'https://kerjago.test',
    ]);
    $live = Job::factory()->for($profile, 'employerProfile')->create();

    $this->actingAs($profile->user)
        ->put(route('employer.profile.update'), [
            'company_name' => 'Kerjago Labs',
            'industry' => 'Logistics',
            'country' => 'ID',
            'city' => 'Bandung',
            'website' => 'https://kerjago.test',
        ])
        ->assertRedirect();

    expect($profile->refresh()->isVerified())->toBeTrue()
        ->and($live->refresh()->status)->toBe(JobStatus::Active)
        ->and(EmployerVerificationEvent::query()->count())->toBe(0);
});

test('an employer can ask to be reviewed, once', function () {
    $profile = EmployerProfile::factory()->unverified()->create();

    $this->actingAs($profile->user)
        ->post(route('employer.verification.request'))
        ->assertRedirect();

    $requestedAt = $profile->refresh()->verification_requested_at;

    expect($requestedAt)->not->toBeNull();

    $this->travel(1)->days();

    $this->actingAs($profile->user)->post(route('employer.verification.request'));

    expect($profile->refresh()->verification_requested_at?->eq($requestedAt))->toBeTrue();
});

/**
 * An employer sitting in a conversation of the given kind.
 *
 * @return array{0: EmployerProfile, 1: Conversation}
 */
function employerConversation(ConversationKind $kind, bool $verified = false): array
{
    $profile = $verified
        ? EmployerProfile::factory()->verified()->create()
        : EmployerProfile::factory()->unverified()->create();

    $conversation = Conversation::factory()
        ->kind($kind->value)
        ->has(Participant::factory()->for_($profile->user_id), 'participants')
        ->has(Participant::factory(), 'participants')
        ->create();

    return [$profile, $conversation];
}
