<?php

namespace App\Http\Controllers\Employer;

use App\Actions\Applications\UpdateApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateApplicationStatusRequest;
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

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Applicant status updated.')]);

        return back();
    }
}
