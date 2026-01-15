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

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Blue,
                'danger' => Color::Red,
                'gray' => Color::Gray,
                'info' => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandLogo(fn() => view('filament.logo'))
            ->brandLogoHeight('3.5rem')
            ->font('Nunito')
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
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
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                'panels::body.end',
                fn() => view('partials.chatwoot')
            )
            ->navigationGroups([
                'Gestão de Revendas',
                'Gestão de Usuários e Clientes',
                'Catálogo de Softwares',
                'Licenciamento e Ativações',
                'Conteúdo & Suporte',
                'Sistema e Site',
            ])
            ->navigationItems([
                \Filament\Navigation\NavigationItem::make('Documentação SDK')
                    ->url(fn() => route('docs.index'))
                    ->icon('heroicon-o-book-open')
                    ->group('Conteúdo & Suporte')
                    ->sort(999),
            ])
            ->renderHook(
                'panels::body.end',
                fn() => view('filament.custom-styles'),
            )
            ->plugins([
                \Jeffgreco13\FilamentBreezy\BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: true,
                        shouldRegisterNavigation: false,
                        hasAvatars: false, // Pode ativar se quiser avatars
                        slug: 'meu-perfil'
                    )
                    ->enableTwoFactorAuthentication(
                        force: fn() => in_array(auth()->user()?->acesso, [1, 2]), // Obriga Admin (1) e Revenda (2)
                    ),
            ]);
    }
}
