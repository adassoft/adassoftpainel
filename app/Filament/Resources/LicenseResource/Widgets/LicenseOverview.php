<?php

namespace App\Filament\Resources\LicenseResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LicenseOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total de Licenças', \App\Models\License::count())
                ->icon('heroicon-o-key'),

            Stat::make('Licenças Ativas', \App\Models\License::where('status', 'ativo')->where('data_expiracao', '>=', now())->count())
                ->description('Licenças válidas e ativas')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Expirando (7 dias)', \App\Models\License::where('status', 'ativo')
                ->where('data_expiracao', '>=', now())
                ->where('data_expiracao', '<=', now()->addDays(7))
                ->count())
                ->description('Atenção necessária')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning')
                ->url(\App\Filament\Resources\LicenseResource::getUrl('index', ['tableFilters[expirando_7_dias][isActive]' => 1])),

            Stat::make('Licenças Expiradas', \App\Models\License::where('data_expiracao', '<', now())->count())
                ->description('Total expirado')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }
}
