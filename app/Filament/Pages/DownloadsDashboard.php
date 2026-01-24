<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\DownloadsStats;
use App\Filament\Widgets\DownloadsChart;

class DownloadsDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.pages.downloads-dashboard';
    protected static ?string $navigationGroup = 'Relatórios';
    protected static ?string $title = 'Analytics de Downloads';
    protected static ?int $navigationSort = 2;

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DownloadsStats::class,
            DownloadsChart::class,
            \App\Filament\Widgets\DownloadsPieChart::class,
            \App\Filament\Widgets\DownloadsReferersWidget::class,
            \App\Filament\Widgets\DownloadsRankingWidget::class,
        ];
    }
}
