<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\DownloadLog;
use App\Models\Download;

class DownloadsStats extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Hoje
        $downloadsToday = DownloadLog::whereDate('created_at', today())->count();

        // Mês
        $downloadsMonth = DownloadLog::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Top Software Hoje
        $topToday = DownloadLog::whereDate('created_at', today())
            ->selectRaw('download_id, count(*) as total')
            ->groupBy('download_id')
            ->orderByDesc('total')
            ->with('download')
            ->first();

        $topName = $topToday?->download->titulo ?? '-';
        $topCount = $topToday?->total ?? 0;

        // Total Geral (Contador Legado + Logs) não mistura bem, melhor usar o contador do Download::sum() para 'Total Histórico'
        $totalHistorico = Download::sum('contador');

        return [
            Stat::make('Total Histórico', number_format($totalHistorico, 0, ',', '.'))
                ->description('Downloads totais acumulados')
                ->descriptionIcon('heroicon-o-archive-box')
                ->color('gray'),

            Stat::make('Downloads (Hoje)', $downloadsToday)
                ->description('Registros diários')
                ->descriptionIcon('heroicon-o-arrow-path')
                ->chart([$downloadsToday, $downloadsToday + 2, $downloadsToday + 5]) // Mock visual
                ->color('success'),

            Stat::make('Downloads (Mês)', $downloadsMonth)
                ->description('Neste mês')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('primary'),

            Stat::make('Top Hoje', $topName)
                ->description("{$topCount} downloads")
                ->descriptionIcon('heroicon-o-star')
                ->color('warning'),
        ];
    }
}
