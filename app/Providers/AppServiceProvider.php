<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production') || \Illuminate\Support\Str::startsWith(config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
            $this->app['request']->server->set('HTTPS', 'on');
        }

        \App\Models\Software::observe(\App\Observers\SoftwareObserver::class);
        \App\Models\Plan::observe(\App\Observers\PlanObserver::class);
        \App\Models\ResellerConfig::observe(\App\Observers\ResellerConfigObserver::class);
    }
}
