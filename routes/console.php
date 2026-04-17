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

// Push notification: remind users who haven't recorded transactions today
Schedule::command('app:send-transaction-reminder')->dailyAt('20:00')->name('transaction-reminder');

// Push notification: warn users whose subscription is expiring soon
Schedule::command('app:send-subscription-expiry-reminder')->dailyAt('09:00')->name('subscription-expiry-reminder');
