<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Order;
use App\Models\License;
use App\Models\Software;
use App\Models\Company;

class DashboardStatsOverview extends BaseWidget
{
    // protected static string $view = 'filament.widgets.custom-stats-overview'; // Remover custom view se quiser usar o padrão Sparkline bonito do V3
    // Se a view custom nao suportar descriptionChart, melhor usar padrão.
    // O usuário tinha um arquivo custom view. Vou comentar e usar o padrão para ter o visual moderno do Filament com gráficos.

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // 1. Receita Mês (MRR Proxy)
        $startMonth = now()->startOfMonth();
        $endMonth = now()->endOfMonth();
        $startLastMonth = now()->subMonth()->startOfMonth();
        $endLastMonth = now()->subMonth()->endOfMonth();

        $validStatuses = ['paid', 'pago', 'approved', 'completed'];

        $revenueCurrent = Order::whereIn('status', $validStatuses)->whereBetween('created_at', [$startMonth, $endMonth])->sum('valor');
        $revenueLast = Order::whereIn('status', $validStatuses)->whereBetween('created_at', [$startLastMonth, $endLastMonth])->sum('valor');

        $diffRevenue = $revenueCurrent - $revenueLast;
        $descRevenue = $diffRevenue >= 0 ? 'Aumento de ' . number_format($diffRevenue, 2, ',', '.') : 'Queda de ' . number_format(abs($diffRevenue), 2, ',', '.');
        $iconRevenue = $diffRevenue >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
        $colorRevenue = $diffRevenue >= 0 ? 'success' : 'danger';

        // Chart data (last 7 days revenue)
        $chartRevenue = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartRevenue[] = Order::whereIn('status', $validStatuses)->whereDate('created_at', $date)->sum('valor');
        }

        // 2. Licenças Ativas
        $activeLicenses = License::where('status', 1)->where('data_expiracao', '>=', now())->count();
        // Tendencia de novas licenças nos ultimos 30 dias
        $newLicensesMonth = License::where('data_criacao', '>=', now()->subDays(30))->count();

        // 3. Validações nas ultimas 24h (Atividade)
        // Se usar LogTable. (Assumindo ValidationLog model)
        // $validations24h = \App\Models\ValidationLog::where('created_at', '>=', now()->subDay())->count();

        return [
            Stat::make('Receita Este Mês', 'R$ ' . number_format($revenueCurrent, 2, ',', '.'))
                ->description($descRevenue . ' vs mês anterior')
                ->descriptionIcon($iconRevenue)
                ->color($colorRevenue)
                ->chart($chartRevenue),

            Stat::make('Licenças Ativas', $activeLicenses)
                ->description('+' . $newLicensesMonth . ' novas licenças (30d)')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('primary')
                ->chart([$activeLicenses - 5, $activeLicenses - 2, $activeLicenses]), // Mock trend visual

            Stat::make('Catálogo de Softwares', Software::count())
                ->description('Produtos disponíveis')
                ->color('gray'),
        ];
    }
}
