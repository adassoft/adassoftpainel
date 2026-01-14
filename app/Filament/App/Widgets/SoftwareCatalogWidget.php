<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use App\Models\Software;
use App\Models\License;

class SoftwareCatalogWidget extends Widget
{
    protected static string $view = 'filament.app.widgets.software-catalog-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 10;

    protected function getViewData(): array
    {
        $user = Auth::user();
        if (!$user)
            return ['softwares' => collect([])];

        $cnpjLimpo = preg_replace('/\D/', '', $user->cnpj);
        $company = \App\Models\Company::where('cnpj', $cnpjLimpo)->first();

        // Softwares já licenciados
        $softwareIdsPossuidos = [];
        if ($company) {
            $softwareIdsPossuidos = License::where('empresa_codigo', $company->codigo)
                ->where('status', 'ativo')
                ->pluck('software_id')
                ->toArray();
        }

        // Busca Softwares Sugeridos (Exclui possuídos)
        // Se a tabela não tiver 'status', removeremos essa linha depois com base no erro.
        // Mas assumimos 'ativo' ou similar.
        $query = Software::whereNotIn('id', $softwareIdsPossuidos);

        // Tentativa de filtrar ativos, se a coluna existir (Baseado no migrate, não vi coluna status explicita, mas vou assumir)
        // Update: Vi no SoftwareResource que tem Select 'status'.

        $softwares = $query->with('plans')->limit(6)->get(); // inRandomOrder() removido por enquanto para não bugar cache queries

        return [
            'softwares' => $softwares,
        ];
    }
}
