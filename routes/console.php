<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

\Illuminate\Support\Facades\Schedule::job(new \App\Jobs\RefreshMercadoLibreTokens)->hourly();
\Illuminate\Support\Facades\Schedule::job(new \App\Jobs\CheckBillingNotifications)->dailyAt('09:00');
