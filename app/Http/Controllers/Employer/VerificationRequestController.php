<?php

namespace App\Http\Controllers\Employer;

use App\Actions\Verification\RequestVerification;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VerificationRequestController extends Controller
{
    /**
     * A company asks to be reviewed.
     *
     * This is what the Talent Search wall's call to action points at: an
     * employer who is shown candidates they cannot reach needs somewhere for
     * that interest to go, and posting a job is otherwise the only way into the
     * queue.
     *
     * Idempotent, and deliberately so — clicking twice must not restart the
     * clock staff sort the queue by.
     */
    public function store(Request $request, RequestVerification $requestVerification): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_if($user->employerProfile === null, 403);

        $requestVerification->handle($user->employerProfile);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __("Thanks — we'll review your company shortly."),
        ]);

        return back();
    }
}
