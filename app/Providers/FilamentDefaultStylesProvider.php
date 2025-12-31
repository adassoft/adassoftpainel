<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class FilamentDefaultStylesProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configurações Globais para Botões de Ação do Filament
        \Filament\Tables\Actions\Action::configureUsing(function (\Filament\Tables\Actions\Action $action) {
            $action
                ->size(\Filament\Support\Enums\ActionSize::Medium)
                ->extraAttributes(['class' => 'force-btn-height']);
        });

        \Filament\Tables\Actions\EditAction::configureUsing(function (\Filament\Tables\Actions\EditAction $action) {
            $action
                ->size(\Filament\Support\Enums\ActionSize::Medium)
                ->extraAttributes(['class' => 'force-btn-height']);
        });

        \Filament\Tables\Actions\DeleteAction::configureUsing(function (\Filament\Tables\Actions\DeleteAction $action) {
            $action
                ->size(\Filament\Support\Enums\ActionSize::Medium)
                ->extraAttributes(['class' => 'force-btn-height']);
        });

        \Filament\Tables\Actions\ViewAction::configureUsing(function (\Filament\Tables\Actions\ViewAction $action) {
            $action
                ->size(\Filament\Support\Enums\ActionSize::Medium)
                ->extraAttributes(['class' => 'force-btn-height']);
        });
    }
}
