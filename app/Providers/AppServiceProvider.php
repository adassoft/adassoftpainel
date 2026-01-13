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
        \App\Models\User::observe(\App\Observers\OnboardingObserver::class);
        \App\Models\Order::observe(\App\Observers\OrderObserver::class);
        \App\Models\License::observe(\App\Observers\LicenseObserver::class);

        // Carrega Configuração de E-mail do Banco de Dados
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('configuracoes')) { // Evita erro no setup inicial
                $emailConfig = \App\Models\Configuration::where('chave', 'email_config')->first();
                if ($emailConfig) {
                    $config = json_decode($emailConfig->valor, true);
                    if ($config) {
                        \Illuminate\Support\Facades\Config::set('mail.mailers.smtp.host', $config['host'] ?? null);
                        \Illuminate\Support\Facades\Config::set('mail.mailers.smtp.port', $config['port'] ?? 587);
                        \Illuminate\Support\Facades\Config::set('mail.mailers.smtp.encryption', $config['secure'] ?? 'tls');
                        \Illuminate\Support\Facades\Config::set('mail.mailers.smtp.username', $config['username'] ?? null);
                        \Illuminate\Support\Facades\Config::set('mail.mailers.smtp.password', $config['password'] ?? null);
                        \Illuminate\Support\Facades\Config::set('mail.from.address', $config['from_email'] ?? 'noreply@adassoft.com');
                        \Illuminate\Support\Facades\Config::set('mail.from.name', $config['from_name'] ?? 'Adassoft');
                        \Illuminate\Support\Facades\Config::set('mail.default', 'smtp');
                    }
                }
            }
        } catch (\Exception $e) {
            // Silencioso em caso de erro de DB ou migração
        }

        // Log de Emails
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Mail\Events\MessageSent::class,
            \App\Listeners\LogEmailSent::class
        );
    }
}
