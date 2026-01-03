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

        // Fix para White Label: Ajusta APP_URL dinamicamente conforme o domínio de acesso
        // Isso previne erros de CORS/Livewire quando acessado via domínio da revenda
        if (!app()->runningInConsole() && isset($_SERVER['HTTP_HOST'])) {
            $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
            $currentUrl = $scheme . $_SERVER['HTTP_HOST'];

            \Illuminate\Support\Facades\Config::set('app.url', $currentUrl);
            \Illuminate\Support\Facades\URL::forceRootUrl($currentUrl);
        }

        \App\Models\Software::observe(\App\Observers\SoftwareObserver::class);
        \App\Models\Plan::observe(\App\Observers\PlanObserver::class);
        \App\Models\ResellerConfig::observe(\App\Observers\ResellerConfigObserver::class);
        \App\Models\DownloadVersion::observe(\App\Observers\DownloadVersionObserver::class);
    }
}
