<?php

namespace App\Filament\Reseller\Widgets;

use App\Models\License;
use App\Models\Company;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class ResellerStatsOverview extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 5;
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        // Reseller CNPJ identifies the licenses
        $cnpjRevenda = $user->cnpj;

        // Base Query
        $baseQuery = License::where('cnpj_revenda', $cnpjRevenda);

        // 1. Total Active (In date + Active status)
        // Using same logic as legacy: Total - Expired - Soon
        // But doing it via SQL is safer:
        // 'Em Dia' = active status AND expiration > 15 days from now
        $totalEmDia = (clone $baseQuery)
            ->where('status', 'ativo')
            ->where('data_expiracao', '>', now()->addDays(15))
            ->count();

        // 2. Expiring Soon (Between now and 15 days)
        $vencemBreve = (clone $baseQuery)
            ->where('status', 'ativo')
            ->whereBetween('data_expiracao', [now(), now()->addDays(15)])
            ->count();

        // 3. Expired (Date < now)
        $vencidas = (clone $baseQuery)
            ->where('data_expiracao', '<', now())
            ->count();

        // 4. Total Licenses
        $totalGeral = (clone $baseQuery)->count();

        // 5. Balance (from Company table)
        // Assuming the User is linked to a Company via CNPJ
        $saldo = Company::where('cnpj', $cnpjRevenda)->value('saldo') ?? 0;

        return [
            Stat::make('Licenças Ativas', $totalEmDia)
                ->description('Em dia')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('A Vencer', $vencemBreve)
                ->description('Próximos 15 dias')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),

            Stat::make('Vencidas', $vencidas)
                ->description('Renovação necessária')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Total de Licenças', $totalGeral)
                ->description('Todas as licenças')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary'),

            Stat::make('Saldo Disponível', 'R$ ' . number_format($saldo, 2, ',', '.'))
                ->description('Créditos para uso')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info')
            // ->extraAttributes([
            //     'class' => 'cursor-pointer',
            //     'onclick' => "window.location.href = '" . url('/revenda/minha-carteira') . "'",
            // ])
            ,
        ];
    }
}
