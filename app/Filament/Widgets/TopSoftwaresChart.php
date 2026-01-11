<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\License;
use Illuminate\Support\Facades\DB;

class TopSoftwaresChart extends ChartWidget
{
    protected static ?string $heading = 'Distribuição de Licenças por Software';

    protected static ?int $sort = 4;

    protected static ?string $maxHeight = '300px';
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $data = License::select('software_id', DB::raw('count(*) as total'))
            ->with('software')
            ->groupBy('software_id')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $labels = $data->map(fn($row) => $row->software->nome_software ?? 'ID: ' . $row->software_id)->toArray();
        $values = $data->pluck('total')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Total Ativo',
                    'data' => $values,
                    'backgroundColor' => [
                        '#3b82f6', // blue
                        '#10b981', // emerald
                        '#f59e0b', // amber
                        '#ef4444', // red
                        '#8b5cf6', // violet
                        '#ec4899', // pink
                        '#6366f1', // indigo
                        '#14b8a6', // teal
                    ],
                    'hoverOffset' => 4,
                ]
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
