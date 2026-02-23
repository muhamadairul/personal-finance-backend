<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cleanup API logs older than 30 days
Schedule::call(function () {
    \App\Models\ApiLog::where('created_at', '<', now()->subDays(30))->delete();
})->daily()->name('cleanup-api-logs');
