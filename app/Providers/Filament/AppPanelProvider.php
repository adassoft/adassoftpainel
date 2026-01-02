<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->path('app')
            ->login(\App\Filament\App\Pages\Auth\Login::class) // Custom Login Page
            ->registration(\App\Filament\App\Pages\Auth\Register::class)
            ->passwordReset(\App\Filament\App\Pages\Auth\ForgotPassword::class)
            ->colors([
                'primary' => Color::Blue,
            ])
            // ->viteTheme('resources/css/filament/admin/theme.css') // Usando o mesmo tema do Admin
            ->brandLogo(fn() => view('filament.logo'))
            ->brandLogoHeight('3.5rem')
            ->font('Nunito')
            ->sidebarCollapsibleOnDesktop()
            ->renderHook(
                'panels::head.start',
                function () {
                    $iconeUrl = \App\Services\ResellerBranding::getCurrent()['icone_url'] ?? asset('favicon.svg');
                    return \Illuminate\Support\Facades\Blade::render(
                        '<link rel="icon" href="{{ $iconeUrl }}" />',
                        ['iconeUrl' => $iconeUrl]
                    );
                }
            )
            ->renderHook(
                'panels::body.end',
                fn() => view('filament.custom-styles'),
            )
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\\Filament\\App\\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\\Filament\\App\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\\Filament\\App\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class, // Remove info padrão
                \App\Filament\Widgets\DashboardNews::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->renderHook(
                'panels::body.end',
                fn() => view('partials.chatwoot')
            )
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
