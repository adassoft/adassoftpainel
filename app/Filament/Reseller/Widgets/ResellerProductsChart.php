<?php

namespace App\Filament\Reseller\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\License;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ResellerProductsChart extends ChartWidget
{
    protected static ?string $heading = 'Softwares Mais Ativos';
    protected static ?int $sort = 3;
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $user = Auth::user();
        if (!$user)
            return [];
        $cnpj = $user->cnpj;

        $data = License::select('software_id', DB::raw('count(*) as total'))
            ->where('cnpj_revenda', $cnpj)
            // Tenta cobrir ambos os casos comuns de legado vs novo até padronização
            ->where(function ($q) {
                $q->where('status', 'ativo')->orWhere('status', 1);
            })
            ->with('software')
            ->groupBy('software_id')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $labels = $data->map(fn($row) => $row->software->nome_software ?? 'Soft ' . $row->software_id)->toArray();
        $values = $data->pluck('total')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Total Ativo',
                    'data' => $values,
                    'backgroundColor' => [
                        '#3b82f6',
                        '#8b5cf6',
                        '#ec4899',
                        '#f43f5e',
                        '#10b981',
                        '#f59e0b'
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
