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

        // Busca a Company do usuário (Priority: ID -> Legacy CNPJ)
        if ($user->empresa_id) {
            $company = \App\Models\Company::where('codigo', $user->empresa_id)->first();
        } else {
            $cnpjLimpo = preg_replace('/\D/', '', $user->cnpj);
            $company = \App\Models\Company::where('cnpj', $cnpjLimpo)->first();
        }

        // Softwares já licenciados
        $softwareIdsPossuidos = [];
        if ($company) {
            $softwareIdsPossuidos = License::where('empresa_codigo', $company->codigo)
                ->where('status', 'ativo')
                ->pluck('software_id')
                ->toArray();
        }

        // Busca Softwares (Query Base)
        $query = Software::query();

        // Filter by Reseller Active Plans
        $cnpjReseller = \App\Services\ResellerBranding::getCurrentCnpj();

        if ($cnpjReseller && $cnpjReseller !== '00000000000100') {
            $query->whereHas('plans', function ($q) use ($cnpjReseller) {
                $q->whereHas('configs', function ($q2) use ($cnpjReseller) {
                    $q2->where('cnpj_revenda', $cnpjReseller)
                        ->where('ativo', true);
                });
            });
        }

        $softwares = $query->with('plans')->limit(9)->get();

        return [
            'softwares' => $softwares,
            'ownedIds' => $softwareIdsPossuidos,
        ];
    }
}
