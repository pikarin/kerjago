<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Two entries rather than one wrapper: a failure in the job sweep must not
// stop candidates being re-locked, and vice versa.
Schedule::command('jobs:expire')->dailyAt('00:10')->withoutOverlapping();
Schedule::command('unlocks:expire')->dailyAt('00:20')->withoutOverlapping();
