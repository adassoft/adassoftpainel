<?php

namespace App\Filament\Reseller\Pages;

use App\Models\License;
use App\Models\Company;
use App\Models\Software;
use Filament\Forms\Get;
use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class ManageTerminals extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';
    protected static ?string $navigationLabel = 'Gerenciar Terminais';
    protected static ?string $navigationGroup = 'Gestão de Clientes';
    protected static ?int $navigationSort = 5;
    protected static ?string $title = 'Terminais por Licença (Revenda)';
    protected static string $view = 'filament.pages.manage-terminals';

    public ?array $filters = [
        'empresa_id' => null,
        'software_id' => null,
    ];

    public function mount()
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Grid::make(2)
                    ->schema([
                        Select::make('empresa_id')
                            ->label('Empresa')
                            ->placeholder('Selecione uma empresa...')
                            // Filter companies linked to this reseller
                            ->options(
                                Company::where('cnpj_representante', auth()->user()->cnpj)
                                    ->orWhere('cnpj_representante', auth()->user()->empresa?->cnpj) // Fallback if user->cnpj empty
                                    ->pluck('razao', 'codigo')
                            )
                            ->searchable()
                            ->live(),
                        Select::make('software_id')
                            ->label('Software')
                            ->placeholder('Selecione...')
                            ->options(Software::pluck('nome_software', 'id'))
                            ->searchable()
                            ->live(),
                    ]),
            ])
            ->statePath('filters');
    }

    public function getViewData(): array
    {
        // Se nenhum filtro estiver aplicado, retorna lista vazia para performance (Igual Admin)
        if (empty($this->filters['empresa_id']) && empty($this->filters['software_id'])) {
            return [
                'licencas' => collect(),
            ];
        }

        $userCnpj = auth()->user()->cnpj;
        if (!$userCnpj) {
            $userCnpj = auth()->user()->empresa?->cnpj;
        }

        // Base Query: License linked to Reseller
        $query = License::with(['software', 'company'])
            ->where('cnpj_revenda', $userCnpj)
            ->whereIn('status', ['ativo', 'suspenso']);

        // Filter by Company (if selected)
        if (!empty($this->filters['empresa_id'])) {
            $query->where('empresa_codigo', $this->filters['empresa_id']);
        }

        // Filter by Software
        if (!empty($this->filters['software_id'])) {
            $query->where('software_id', $this->filters['software_id']);
        }

        $licencas = $query->orderByDesc('data_criacao')->get();

        foreach ($licencas as $licenca) {
            $licenca->dados_terminais = $licenca->merged_terminals;
            $licenca->total_ativos = collect($licenca->dados_terminais)->where('ativo', 1)->count();
            $licenca->total_disponiveis = max(0, $licenca->terminais_permitidos - $licenca->total_ativos);
        }

        return [
            'licencas' => $licencas,
        ];
    }

    protected function checkOwnership($licencaId)
    {
        $licenca = License::find($licencaId);
        if (!$licenca)
            return null;

        $userCnpj = auth()->user()->cnpj ?? auth()->user()->empresa?->cnpj;

        // Sanitize CNPJs for comparison if needed, but assuming exact match in DB
        // If DB has masks, need cleaning. Step 4968 view shows $licenca->cnpj_revenda usage.
        // Assuming strict match for security or normalized data.

        if ($licenca->cnpj_revenda !== $userCnpj) {
            // Try cleaned comparison just in case
            $cleanL = preg_replace('/\D/', '', $licenca->cnpj_revenda ?? '');
            $cleanU = preg_replace('/\D/', '', $userCnpj ?? '');
            if ($cleanL !== $cleanU) {
                return null; // Not Owner
            }
        }
        return $licenca;
    }

    public function disable($licencaId, $terminalId)
    {
        $license = $this->checkOwnership($licencaId);
        if (!$license) {
            Notification::make()->danger()->title('Acesso negado a esta licença.')->send();
            return;
        }

        try {
            DB::table('terminais_software')
                ->where('licenca_id', $licencaId)
                ->where('terminal_codigo', $terminalId)
                ->update(['ativo' => 0]);

            $this->recalcLicenseUsage($licencaId);

            Notification::make()->success()->title('Terminal desabilitado.')->send();
        } catch (\Exception $e) {
            Notification::make()->danger()->title('Erro ao desabilitar: ' . $e->getMessage())->send();
        }
    }

    public function remove($licencaId, $terminalId, $instalacaoId, $mac)
    {
        $license = $this->checkOwnership($licencaId);
        if (!$license) {
            Notification::make()->danger()->title('Acesso negado a esta licença.')->send();
            return;
        }

        if ($terminalId === 'null' || $terminalId === '')
            $terminalId = null;
        if ($instalacaoId === 'null')
            $instalacaoId = null;

        try {
            DB::beginTransaction();

            if ($instalacaoId) {
                DB::table('licenca_instalacoes')
                    ->where('licenca_id', $licencaId)
                    ->where('instalacao_id', $instalacaoId)
                    ->delete();
            }
            if ($mac) {
                DB::table('licenca_instalacoes')
                    ->where('licenca_id', $licencaId)
                    ->where('mac_address', $mac)
                    ->delete();
            }

            if ($terminalId) {
                DB::table('terminais_software')
                    ->where('licenca_id', $licencaId)
                    ->where('terminal_codigo', $terminalId)
                    ->update(['ativo' => 0]);
            }

            $this->recalcLicenseUsage($licencaId);

            DB::commit();
            Notification::make()->success()->title('Instalação removida com sucesso.')->send();

        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()->danger()->title('Erro ao remover: ' . $e->getMessage())->send();
        }
    }

    protected function recalcLicenseUsage($licencaId)
    {
        $license = License::find($licencaId);
        if ($license) {
            $license->recalculateUsage();
        }
    }
}
