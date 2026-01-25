<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

\Illuminate\Support\Facades\Schedule::job(new \App\Jobs\RefreshMercadoLibreTokens)->hourly();
\Illuminate\Support\Facades\Schedule::job(new \App\Jobs\CheckBillingNotifications)->dailyAt('09:00');

// Process Queue on Schedule (Fallback for shared hosting/Easypanel cron)
\Illuminate\Support\Facades\Schedule::command('queue:work --stop-when-empty --tries=3')
    ->everyMinute()
    ->withoutOverlapping();

// Check for Scheduled Campaigns
\Illuminate\Support\Facades\Schedule::command('campaigns:check')->everyMinute();
