<?php

namespace App\Http\Controllers\Employer;

use App\Actions\Chat\StartColdOutreach;
use App\Http\Controllers\Controller;
use App\Models\JobseekerProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TalentChatController extends Controller
{
    /**
     * An employer opens a conversation with a jobseeker from Talent Search.
     *
     * Idempotent per pair, so this doubles as "go to our conversation" — the
     * button never creates a duplicate thread.
     */
    public function store(
        Request $request,
        JobseekerProfile $jobseekerProfile,
        StartColdOutreach $startColdOutreach,
    ): RedirectResponse {
        // Two gates, deliberately: 'view' is the same profile-visibility check
        // the adjacent talent.show endpoint applies, and 'viewContact' is the
        // Candidate Unlock rule — chat is a contact channel, so leaving it open
        // would route straight around the mask (ADR 0013). StartColdOutreach
        // refuses too; this is what turns that into a clean 403 rather than an
        // exception from inside the Action.
        Gate::authorize('view', $jobseekerProfile);
        Gate::authorize('viewContact', $jobseekerProfile);

        /** @var User $user */
        $user = $request->user();

        $conversation = $startColdOutreach->handle($user, $jobseekerProfile);

        return to_route('chat.show', $conversation);
    }
}
