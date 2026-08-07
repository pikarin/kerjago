<?php

use App\Actions\Applications\ApplyToJob;
use App\Models\EmployerProfile;
use App\Models\JobseekerProfile;

/**
 * The broad, cheap alarm: every page a role can reach, asserted only to boot
 * without console noise. Feature tests render the same routes but never execute
 * the bundle, so a Vue error thrown during mount — a missing prop, a renamed
 * export, a component removed from a barrel file — passes them all and shows the
 * user a blank panel.
 *
 * Deliberately shallow. The journeys through these pages are asserted in the
 * per-role files beside this one; what this file adds is breadth.
 *
 * assertNoSmoke() is the pair of assertNoJavaScriptErrors() and
 * assertNoConsoleLogs() in one call — a console warning counts, so a page that
 * boots but complains still fails.
 */
test('the public pages boot without console noise', function () {
    activeJob();

    $pages = visit(['/', '/jobs']);

    $pages->assertNoSmoke();
});

test('the auth pages boot without console noise', function () {
    $pages = visit(['/login', '/register', '/forgot-password']);

    $pages->assertNoSmoke();
});

test('every jobseeker page boots without console noise', function () {
    $profile = JobseekerProfile::factory()->create();
    $job = activeJob();

    app(ApplyToJob::class)->handle($profile, $job);

    $this->actingAs($profile->user);

    // Chat is included, and is the reason this stops at the conversation list:
    // opening a conversation mounts the Echo client, which has no Reverb server
    // to reach in a test and would report the failed socket as console noise.
    $pages = visit([
        '/dashboard',
        '/applications',
        '/profile',
        '/chat',
        '/settings/profile',
        '/settings/security',
        '/settings/appearance',
        "/jobs/{$job->id}",
    ]);

    $pages->assertNoSmoke();
});

test('every employer page boots without console noise', function () {
    $employer = EmployerProfile::factory()->verified()->create();
    $job = activeJob(['employer_profile_id' => $employer->id]);

    app(ApplyToJob::class)->handle(JobseekerProfile::factory()->create(), $job);

    $this->actingAs($employer->user);

    $pages = visit([
        '/dashboard',
        '/employer/jobs',
        '/employer/jobs/create',
        "/employer/jobs/{$job->id}/edit",
        "/employer/jobs/{$job->id}/applicants",
        '/employer/talent',
        '/employer/company',
        '/chat',
        '/settings/profile',
        '/settings/security',
    ]);

    $pages->assertNoSmoke();
});
