<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Receita Mensal (Últimos 12 Meses)';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        // Agrupamento por Ano-Mês
        // Assumindo MySQL. SQLite seria strftime.
        $data = Order::select(
            DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month_year"),
            DB::raw("SUM(valor) as total")
        )
            ->where('status', 'paid')
            ->where('created_at', '>=', now()->subYear())
            ->groupBy('month_year')
            ->orderBy('month_year')
            ->get();

        // Preencher meses vazios com 0 se necessário, mas para MVP vamos direto.

        $dates = $data->map(fn($row) => Carbon::createFromFormat('Y-m', $row->month_year)->format('M/y'))->toArray();
        $values = $data->map(fn($row) => (float) $row->total)->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Receita Confirmada (R$)',
                    'data' => $values,
                    'fill' => true,
                    'borderColor' => '#10b981', // Emerald 500
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
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
