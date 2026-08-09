<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Requires a server cron entry: * * * * * php artisan schedule:run — this
// repo has no prior scheduled task, so confirm that cron entry exists.
// The adoption dashboard itself reads a live query scope and stays correct
// even if this hasn't run yet; only proactive reminders depend on it.
Schedule::command('policies:check-adoption-deadlines')->dailyAt('06:00');
Schedule::command('assignments:check-deadlines')->dailyAt('06:15');
Schedule::command('land:check-deadlines')->dailyAt('06:30');
