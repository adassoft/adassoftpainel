<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

class GeographicDistributionChart extends ChartWidget
{
    protected static ?string $heading = 'Onde estão nossos clientes?';

    protected static ?int $sort = 3; // Logo após receita/softwares

    protected static ?string $maxHeight = '300px';

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = 'uf';

    protected function getFilters(): ?array
    {
        return [
            'uf' => 'Por Estado (UF)',
            'cidade' => 'Top 10 Cidades',
        ];
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;

        if ($activeFilter === 'cidade') {
            $data = Company::select('cidade', 'uf', DB::raw('count(*) as total'))
                ->whereNotNull('cidade')
                ->where('cidade', '!=', '')
                ->groupBy('cidade', 'uf') // Agrupar por composto
                ->orderByDesc('total')
                ->limit(10)
                ->get();

            // Format: Cidade - UF
            $labels = $data->map(fn($row) => $row->cidade . '-' . $row->uf)->toArray();
        } else {
            // Default UF
            $data = Company::select('uf', DB::raw('count(*) as total'))
                ->whereNotNull('uf')
                ->where('uf', '!=', '')
                ->groupBy('uf')
                ->orderByDesc('total')
                ->limit(27) // Max UFs no BR
                ->get();

            $labels = $data->pluck('uf')->toArray();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Clientes',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => '#6366f1', // Indigo
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
