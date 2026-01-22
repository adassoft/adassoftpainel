<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\DownloadLog;

class DownloadsChart extends ChartWidget
{
    protected static ?string $heading = 'Tendência de Downloads (30 Dias)';
    protected static ?int $sort = 2;
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        // Últimos 30 dias
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            // Count logs per day
            $count = DownloadLog::whereDate('created_at', $date)->count();

            $data[] = $count;
            $labels[] = $date->format('d/m');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Downloads',
                    'data' => $data,
                    'fill' => true,
                    'borderColor' => '#3b82f6', // Primary Blue
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.4, // Smooth curve
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
