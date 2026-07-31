<?php

namespace App\Http\Controllers\Employer;

use App\Actions\Applications\UpdateApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateApplicationStatusRequest;
use App\Jobs\AnnounceApplicationStatusChange;
use App\Models\Application;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ApplicationStatusController extends Controller
{
    /**
     * Change an applicant's status.
     */
    public function update(
        UpdateApplicationStatusRequest $request,
        Application $application,
        UpdateApplicationStatus $updateApplicationStatus,
    ): RedirectResponse {
        $updateApplicationStatus->handle($application, $request->status());

        // Queued so a chat failure cannot block a status change. Composing here
        // rather than inside UpdateApplicationStatus keeps that Action free of
        // any chat dependency.
        AnnounceApplicationStatusChange::dispatch($application, $request->status())->afterCommit();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Applicant status updated.')]);

        return back();
    }
}
