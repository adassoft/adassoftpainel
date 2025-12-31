<?php

namespace App\Filament\Reseller\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Painel do Revendedor';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Reseller\Widgets\ResellerStatsOverview::class,
            \App\Filament\Reseller\Widgets\ResellerSalesChart::class,
            \App\Filament\Reseller\Widgets\ResellerProductsChart::class,
            \App\Filament\Reseller\Widgets\ResellerExpiringLicensesWidget::class,
            \App\Filament\Widgets\DashboardNews::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 2;
    }
}
