<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Models\ValidationLog;
use App\Models\Company;
use App\Models\Software;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Actions\Action;
use Livewire\Attributes\Validate;

class Dashboard extends BaseDashboard
{
    protected static string $view = 'filament.pages.dashboard';

    // Filtros
    public $software_id = null;
    public $empresa_search = '';
    public $dias = 7;

    // Dados
    public $eventosPorDia = [];
    public $ultimosClientes = [];

    public function mount()
    {
        $this->atualizarDados();
    }

    public function atualizarDados()
    {
        // Lógica simplificada para popular tabelas "iguais" ao sistema antigo

        // 1. Eventos por Dia
        $dataLimite = now()->subDays((int) ($this->dias ?: 7));

        $this->eventosPorDia = ValidationLog::query()
            ->selectRaw('DATE(data_validacao) as data, COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN resultado LIKE '%true%' THEN 1 ELSE 0 END) as ok")
            ->selectRaw("SUM(CASE WHEN resultado NOT LIKE '%true%' THEN 1 ELSE 0 END) as falha")
            ->where('data_validacao', '>=', $dataLimite)
            ->groupBy('data')
            ->orderBy('data', 'DESC')
            ->get();

        // 2. Últimos Clientes
        $clienteQuery = Company::query()->orderBy('codigo', 'DESC')->take(4);

        if ($this->empresa_search) {
            $clienteQuery->where('razao', 'like', "%{$this->empresa_search}%")
                ->orWhere('cnpj', 'like', "%{$this->empresa_search}%");
        }

        $this->ultimosClientes = $clienteQuery->get();
    }

    // Ações para o view (wire:click)
    public function submitFilters()
    {
        $this->atualizarDados();
    }

    // Softwares para o select
    public function getSoftwaresProperty()
    {
        return Software::where('status', 1)->pluck('nome_software', 'id');
    }
}
