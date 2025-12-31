<?php

namespace App\Filament\Reseller\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\License;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ResellerSalesChart extends ChartWidget
{
    protected static ?string $heading = 'Minhas Vendas (Ativações Mensais)';
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $user = Auth::user();
        if (!$user)
            return [];
        $cnpj = $user->cnpj;

        // Ativações nos ultimos 12 meses
        $data = License::select(
            DB::raw("DATE_FORMAT(data_criacao, '%Y-%m') as month_year"),
            DB::raw("count(*) as total")
        )
            ->where('cnpj_revenda', $cnpj)
            ->where('data_criacao', '>=', now()->subYear())
            ->groupBy('month_year')
            ->orderBy('month_year')
            ->get();

        $dates = $data->map(fn($row) => Carbon::createFromFormat('Y-m', $row->month_year)->format('M/y'))->toArray();
        $values = $data->map(fn($row) => (int) $row->total)->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Novas Licenças Ativadas',
                    'data' => $values,
                    'borderColor' => '#10b981', // Emerald
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $dates,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
