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
        // The same profile-visibility gate the adjacent talent.show endpoint
        // applies. Not a consent check — cold outreach is deliberately ungated.
        // Without this, a profile that becomes unviewable for any other reason
        // would 403 on read while still accepting a new conversation.
        Gate::authorize('view', $jobseekerProfile);

        /** @var User $user */
        $user = $request->user();

        $conversation = $startColdOutreach->handle($user, $jobseekerProfile);

        return to_route('chat.show', $conversation);
    }
}
