<?php

use App\Actions\Chat\EnsureApplicationConversation;
use App\Chat\Actions\OpenConversation;
use App\Chat\Actions\PostSystemMessage;
use App\Chat\Data\NewConversation;
use App\Chat\Enums\MessageType;
use App\Chat\Models\Conversation;
use App\Chat\Models\Message;
use App\Enums\ApplicationStatus;
use App\Enums\ChatContextType;
use App\Enums\ConversationKind;
use App\Jobs\AnnounceApplicationStatusChange;
use App\Jobs\OpenApplicationConversation;
use App\Models\Application;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\JobseekerProfile;
use Illuminate\Support\Facades\Queue;

/**
 * @return array{0: Application, 1: string, 2: string}
 */
function applicationWithBothSides(): array
{
    $employerProfile = EmployerProfile::factory()->create();
    $job = Job::factory()->for($employerProfile)->create();
    $jobseekerProfile = JobseekerProfile::factory()->create();

    $application = Application::factory()
        ->for($job)
        ->for($jobseekerProfile)
        ->create();

    return [$application, $employerProfile->user_id, $jobseekerProfile->user_id];
}

test('an application conversation joins the jobseeker and the employer', function () {
    [$application, $employerUserId, $jobseekerUserId] = applicationWithBothSides();

    $conversation = app(EnsureApplicationConversation::class)->handle($application);

    expect($conversation->kind)->toBe(ConversationKind::Application->value)
        ->and($conversation->context_type)->toBe(ChatContextType::Application->value)
        ->and($conversation->context_id)->toBe($application->id)
        ->and($conversation->hasParticipant($employerUserId))->toBeTrue()
        ->and($conversation->hasParticipant($jobseekerUserId))->toBeTrue();
});

test('ensuring the conversation twice yields one conversation', function () {
    [$application] = applicationWithBothSides();

    $first = app(EnsureApplicationConversation::class)->handle($application);
    $second = app(EnsureApplicationConversation::class)->handle($application);

    expect($second->id)->toBe($first->id)
        ->and(Conversation::query()->count())->toBe(1);
});

test('applying to a job queues the conversation rather than opening it inline', function () {
    Queue::fake();

    $employerProfile = EmployerProfile::factory()->create();
    $job = Job::factory()->for($employerProfile)->create();
    $jobseeker = JobseekerProfile::factory()->create();

    $this->actingAs($jobseeker->user)
        ->post(route('jobseeker.jobs.apply', $job))
        ->assertRedirect(route('jobseeker.applications.index'));

    expect(Application::query()->count())->toBe(1);
    Queue::assertPushed(OpenApplicationConversation::class);
});

/**
 * The isolation guarantee. Not a try/catch — the controller's success path never
 * executes chat code, so the application is already committed by the time the
 * queued job runs and fails.
 */
test('an application survives chat failing outright', function () {
    [$application] = applicationWithBothSides();

    $this->app->bind(OpenConversation::class, fn () => new class extends OpenConversation
    {
        public function handle(NewConversation $new): Conversation
        {
            throw new RuntimeException('chat is down');
        }
    });

    expect(fn () => app(OpenApplicationConversation::class, ['application' => $application])
        ->handle(app(EnsureApplicationConversation::class)))
        ->toThrow(RuntimeException::class);

    // The application is untouched, and no half-built conversation was left.
    $this->assertModelExists($application);
    expect(Conversation::query()->count())->toBe(0);
});

test('changing an applicant status posts a system message into the conversation', function () {
    [$application] = applicationWithBothSides();

    app(AnnounceApplicationStatusChange::class, [
        'application' => $application,
        'status' => ApplicationStatus::Shortlisted,
    ])->handle(
        app(EnsureApplicationConversation::class),
        app(PostSystemMessage::class),
    );

    $message = Message::query()->firstOrFail();

    expect($message->type)->toBe(MessageType::System)
        ->and($message->participant_id)->toBeNull()
        ->and($message->body)->toContain('shortlisted');
});

/**
 * Posting a system message is an unconditional insert, so the job needs a guard
 * to be retry-safe. The same guard fixes a live bug: an employer re-selecting
 * the status an application is already in used to post a second identical
 * message.
 */
test('re-announcing the same status does not duplicate the system message', function () {
    [$application] = applicationWithBothSides();

    $announce = fn () => app(AnnounceApplicationStatusChange::class, [
        'application' => $application,
        'status' => ApplicationStatus::Shortlisted,
    ])->handle(
        app(EnsureApplicationConversation::class),
        app(PostSystemMessage::class),
    );

    $announce();
    $announce();

    expect(Message::query()->count())->toBe(1);
});

test('a genuine status transition still posts after an identical earlier one', function () {
    [$application] = applicationWithBothSides();

    $announce = fn (ApplicationStatus $status) => app(AnnounceApplicationStatusChange::class, [
        'application' => $application,
        'status' => $status,
    ])->handle(
        app(EnsureApplicationConversation::class),
        app(PostSystemMessage::class),
    );

    $announce(ApplicationStatus::Shortlisted);
    $announce(ApplicationStatus::Rejected);
    // Shortlisted again is a real transition, because it is no longer the
    // most recent announcement.
    $announce(ApplicationStatus::Shortlisted);

    expect(Message::query()->count())->toBe(3);
});

test('a status change on an application with no conversation creates one first', function () {
    [$application] = applicationWithBothSides();

    expect(Conversation::query()->count())->toBe(0);

    app(AnnounceApplicationStatusChange::class, [
        'application' => $application,
        'status' => ApplicationStatus::Reviewed,
    ])->handle(
        app(EnsureApplicationConversation::class),
        app(PostSystemMessage::class),
    );

    expect(Conversation::query()->count())->toBe(1);
});

test('the employer status endpoint queues the announcement', function () {
    Queue::fake();

    [$application] = applicationWithBothSides();
    $employer = $application->job->employerProfile->user;

    $this->actingAs($employer)
        ->patch(route('employer.applications.status.update', $application), [
            'status' => ApplicationStatus::Shortlisted->value,
        ])
        ->assertRedirect();

    Queue::assertPushed(AnnounceApplicationStatusChange::class);
});
