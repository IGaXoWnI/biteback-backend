<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Define scheduled tasks
Schedule::command('reservations:check-expired')
    ->everyMinute()
    ->appendOutputTo(storage_path('logs/scheduler.log'));
