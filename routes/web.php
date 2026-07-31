<?php

use App\Http\Controllers\ApplicationResumeController;
use App\Http\Controllers\Chat\ConversationController;
use App\Http\Controllers\Chat\MarkReadController;
use App\Http\Controllers\Chat\MessageController;
use App\Http\Controllers\Chat\ReactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Employer\ApplicationStatusController;
use App\Http\Controllers\Employer\EmployerProfileController;
use App\Http\Controllers\Employer\JobApplicantsController;
use App\Http\Controllers\Employer\JobController as EmployerJobController;
use App\Http\Controllers\Employer\TalentChatController;
use App\Http\Controllers\Employer\TalentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\Jobseeker\ApplicationController;
use App\Http\Controllers\Jobseeker\JobseekerProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('jobs', [JobController::class, 'index'])->name('jobs.index');
Route::get('jobs/{job}', [JobController::class, 'show'])->name('jobs.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('applications/{application}/resume', ApplicationResumeController::class)
        ->name('applications.resume');

    // Chat is available to every role, including staff, so it sits outside the
    // role-scoped groups below.
    Route::get('chat', [ConversationController::class, 'index'])->name('chat.index');
    Route::get('chat/{conversation}', [ConversationController::class, 'show'])->name('chat.show');
    Route::post('chat/{conversation}/messages', [MessageController::class, 'store'])->name('chat.messages.store');
    Route::post('chat/{conversation}/read', [MarkReadController::class, 'store'])->name('chat.read');
    Route::post('chat/{conversation}/reactions', [ReactionController::class, 'store'])->name('chat.reactions.store');

    Route::middleware('role:jobseeker')->name('jobseeker.')->group(function () {
        Route::get('profile', [JobseekerProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [JobseekerProfileController::class, 'update'])->name('profile.update');

        Route::middleware('profile.complete')->group(function () {
            Route::get('applications', [ApplicationController::class, 'index'])->name('applications.index');
            Route::post('jobs/{job}/apply', [ApplicationController::class, 'store'])->name('jobs.apply');
        });
    });

    Route::middleware('role:employer')->prefix('employer')->name('employer.')->group(function () {
        Route::get('company', [EmployerProfileController::class, 'edit'])->name('profile.edit');
        Route::put('company', [EmployerProfileController::class, 'update'])->name('profile.update');

        Route::middleware('profile.complete')->group(function () {
            Route::resource('jobs', EmployerJobController::class)
                ->only(['index', 'create', 'store', 'edit', 'update']);

            Route::get('jobs/{job}/applicants', [JobApplicantsController::class, 'index'])
                ->name('jobs.applicants');

            Route::patch('applications/{application}/status', [ApplicationStatusController::class, 'update'])
                ->name('applications.status.update');

            Route::get('talent', [TalentController::class, 'index'])->name('talent.index');
            Route::get('talent/{jobseekerProfile}', [TalentController::class, 'show'])->name('talent.show');

            Route::post('talent/{jobseekerProfile}/chat', [TalentChatController::class, 'store'])
                ->name('talent.chat');
        });
    });
});

require __DIR__.'/settings.php';
