<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationResumeController extends Controller
{
    /**
     * Download the resume snapshot submitted with an application.
     */
    public function __invoke(Application $application): StreamedResponse
    {
        Gate::authorize('downloadResume', $application);

        abort_if($application->resume_path === null, 404);
        abort_unless(Storage::disk('local')->exists($application->resume_path), 404);

        $extension = pathinfo($application->resume_path, PATHINFO_EXTENSION);
        $filename = Str::slug($application->jobseekerProfile->full_name).'-resume'
            .($extension !== '' ? ".{$extension}" : '');

        return Storage::disk('local')->download($application->resume_path, $filename);
    }
}
