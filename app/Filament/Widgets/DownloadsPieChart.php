<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\DownloadLog;
use Illuminate\Support\Facades\DB;

class DownloadsPieChart extends ChartWidget
{
    protected static ?string $heading = 'Top 5 (Distribuição)';
    protected static ?int $sort = 3;
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        // Buscar top 5 downloads baseados nos logs analytics
        $data = DownloadLog::select('download_id', DB::raw('count(*) as total'))
            ->groupBy('download_id')
            ->orderByDesc('total')
            ->limit(5)
            ->with('download')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Downloads',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => [
                        '#3b82f6', // Blue-500
                        '#10b981', // Emerald-500
                        '#f59e0b', // Amber-500
                        '#ef4444', // Red-500
                        '#8b5cf6', // Violet-500
                    ],
                    'hoverOffset' => 4,
                ],
            ],
            'labels' => $data->map(fn($item) => $item->download->titulo ?? 'Item #' . $item->download_id)->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
