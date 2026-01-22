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

    protected function getColumns(): int
    {
        return 3;
    }

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

        // 3. Avaliações Recentes (Filtro Inteligente)
        $avaliacoes = License::where(function ($q) {
            $q->where('is_trial', 1)
                ->orWhere(function ($sub) {
                    $sub->whereNull('data_ultima_renovacao')
                        ->where('data_criacao', '>=', now()->subDays(90))
                        ->whereRaw('DATEDIFF(data_expiracao, data_criacao) < 30');
                });
        })
            ->count();

        // 4. Vitalícias
        $vitalicias = License::where('vitalicia', 1)->count();

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

            Stat::make('Avaliações (Recentes)', $avaliacoes)
                ->description('Conversão Potencial')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('gray')
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:bg-gray-50',
                    'onclick' => "window.location.href = '" . route('filament.admin.resources.licenses.index', ['tableFilters[tipo][value]' => 'avaliacao']) . "'",
                ]),

            Stat::make('Licenças Vitalícias', $vitalicias)
                ->description('Total Perpétuo')
                ->descriptionIcon('heroicon-m-infinity')
                ->color('success')
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:bg-gray-50',
                    'onclick' => "window.location.href = '" . route('filament.admin.resources.licenses.index', ['tableFilters[tipo][value]' => 'vitalicia']) . "'",
                ]),

            Stat::make('Catálogo de Softwares', Software::count())
                ->description('Produtos disponíveis')
                ->color('gray'),
        ];
    }
}
