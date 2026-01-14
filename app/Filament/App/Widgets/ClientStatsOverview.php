<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use App\Models\License;
use App\Models\Suggestion;

class ClientStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = Auth::user();
        if (!$user)
            return [];

        // Busca a Company do usuário (Priority: ID -> Legacy CNPJ)
        if ($user->empresa_id) {
            $company = \App\Models\Company::where('codigo', $user->empresa_id)->first();
        } else {
            $cnpjLimpo = preg_replace('/\D/', '', $user->cnpj);
            $company = \App\Models\Company::where('cnpj', $cnpjLimpo)->first();
        }

        if (!$company) {
            return [
                Stat::make('Erro', 'Empresa não encontrada')
                    ->description('Clique aqui para completar seu cadastro')
                    ->color('danger')
                    ->url(\App\Filament\App\Pages\MyCompany::getUrl()),
            ];
        }

        // Licenças
        $licencasAtivas = License::where('empresa_codigo', $company->codigo)
            ->where('status', 'ativo')
            ->count();

        $licencasExpirando = License::where('empresa_codigo', $company->codigo)
            ->where('status', 'ativo')
            ->where('data_expiracao', '<=', now()->addDays(15))
            ->count();

        // Terminais (Soma de todos os utilizados)
        $terminaisEmUso = License::where('empresa_codigo', $company->codigo)
            ->sum('terminais_utilizados');

        // Sugestões do Usuário
        $sugestoesAprovadas = Suggestion::where('user_id', $user->id)
            ->whereIn('status', ['voting', 'planned', 'in_progress', 'completed'])
            ->count();

        // Sugestões Pendentes
        $sugestoesPendentes = Suggestion::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        return [
            Stat::make('Licenças Ativas', $licencasAtivas)
                ->description('Softwares contratados')
                ->descriptionIcon('heroicon-m-key')
                ->color('success'),

            Stat::make('Atenção', $licencasExpirando)
                ->label('Vencendo (15 dias)')
                ->value($licencasExpirando)
                ->description($licencasExpirando > 0 ? 'Renove suas licenças' : 'Tudo em dia')
                ->descriptionIcon('heroicon-m-clock')
                ->color($licencasExpirando > 0 ? 'danger' : 'success')
                ->url(route('filament.app.resources.licenses.index')),

            Stat::make('Terminais', $terminaisEmUso)
                ->description('Computadores ativos')
                ->descriptionIcon('heroicon-m-computer-desktop')
                ->color('info'),

            Stat::make('Ideias', $sugestoesAprovadas)
                ->description($sugestoesPendentes . ' pendentes de análise')
                ->descriptionIcon('heroicon-m-light-bulb')
                ->color('primary')
                ->url(route('filament.app.pages.feature-requests')),
        ];
    }
}
