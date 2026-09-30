<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('assessment:activate-due-programs')->everyMinute();
Schedule::command('assessment:send-due-invitations')->everyMinute()->withoutOverlapping();
Schedule::command('assessment:complete-ended-programs')->everyMinute();
