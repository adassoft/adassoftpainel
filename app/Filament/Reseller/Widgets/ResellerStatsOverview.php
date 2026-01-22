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
        return 3;
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
        $totalEmDia = (clone $baseQuery)
            ->where('status', 'ativo')
            ->where('data_expiracao', '>', now()->addDays(15))
            ->count();

        // 2. Expiring Soon (Between now and 15 days)
        $vencemBreve = (clone $baseQuery)
            ->where('status', 'ativo')
            ->whereBetween('data_expiracao', [now(), now()->addDays(15)])
            ->count();

        // Filtro para "ignorar" avaliações antigas não convertidas (Lixo)
        // Ignorar se: nunca renovou E duração < 30 dias (4, 7, 15 dias) E expirou há mais de 60 dias
        $ignoreJunk = function ($query) {
            return $query->whereNot(function ($q) {
                $q->whereNull('data_ultima_renovacao')
                    ->whereRaw('DATEDIFF(data_expiracao, data_criacao) < 30')
                    ->where('data_expiracao', '<', now()->subDays(60));
            });
        };

        // 3. Expired (Date < now) - Sanitized
        $vencidas = (clone $baseQuery)
            ->where('data_expiracao', '<', now())
            ->tap($ignoreJunk)
            ->count();

        // 4. Total Licenses - Sanitized
        $totalGeral = (clone $baseQuery)
            ->tap($ignoreJunk)
            ->count();

        // 5. Balance
        $saldo = Company::where('cnpj', $cnpjRevenda)->value('saldo') ?? 0;

        // 6. Avaliações Recentes (Filtro Inteligente)
        // Usa flag explícita OU inferência para legados
        $avaliacoes = (clone $baseQuery)
            ->where(function ($q) {
                $q->where('is_trial', 1)
                    ->orWhere(function ($sub) {
                        $sub->whereNull('data_ultima_renovacao')
                            ->where('data_criacao', '>=', now()->subDays(90))
                            ->whereRaw('DATEDIFF(data_expiracao, data_criacao) < 30');
                    });
            })
            ->count();

        // 7. Vitalícias
        $vitalicias = (clone $baseQuery)->where('vitalicia', 1)->count();

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

            Stat::make('Avaliações (Recentes)', $avaliacoes)
                ->description('Potencial de Conversão')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('gray')
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:bg-gray-50',
                    'onclick' => "window.location.href = '" . route('filament.reseller.resources.licenses.index', ['tableFilters[tipo][value]' => 'avaliacao']) . "'",
                ]),

            Stat::make('Vitalícias', $vitalicias)
                ->description('Licenças Perpétuas')
                ->descriptionIcon('heroicon-m-infinity')
                ->color('success')
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:bg-gray-50',
                    'onclick' => "window.location.href = '" . route('filament.reseller.resources.licenses.index', ['tableFilters[tipo][value]' => 'vitalicia']) . "'",
                ]),

            Stat::make('Total de Licenças', $totalGeral)
                ->description('Todas as licenças')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary'),

            Stat::make('Saldo Disponível', 'R$ ' . number_format($saldo, 2, ',', '.'))
                ->description('Créditos para uso')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),
        ];
    }
}
