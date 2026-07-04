<?php

namespace App\Actions\Applications;

use App\Enums\ApplicationStatus;
use App\Models\Application;

class UpdateApplicationStatus
{
    /**
     * Move an application to a new status. Any status is reachable from
     * any other in the MVP — no transition rules.
     */
    public function handle(Application $application, ApplicationStatus $status): Application
    {
        $application->update(['status' => $status]);

        return $application;
    }
}
