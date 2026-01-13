<?php

namespace App\Filament\Pages;

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

    // Definição visual
    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';
    protected static ?string $navigationLabel = 'Gerenciar Terminais';
    protected static ?string $navigationGroup = 'Gestão de Clientes';
    protected static ?int $navigationSort = 5;
    protected static ?string $title = 'Terminais por Licença';
    protected static string $view = 'filament.pages.manage-terminals';

    // Filtros
    public ?array $filters = [
        'empresa_id' => null,
        'software_id' => null,
    ];

    public function mount()
    {
        // Se for cliente, não precisa filtrar empresa (já é fixa)
        // Se for admin, pode filtrar
        $this->form->fill();
    }



    public function form(Form $form): Form
    {
        // Só mostra filtros se for Admin (ou Revenda com múltiplas empresas)
        // Vou assumir Admin por enquanto. Se precisar restringir, uso auth()->user()

        return $form
            ->schema([
                \Filament\Forms\Components\Grid::make(2)
                    ->schema([
                        Select::make('empresa_id')
                            ->label('Empresa')
                            ->placeholder('Digite para buscar...')
                            ->getSearchResultsUsing(fn(string $search) => Company::where('razao', 'like', "%{$search}%")->orWhere('cnpj', 'like', "%{$search}%")->limit(50)->pluck('razao', 'codigo'))
                            ->getOptionLabelUsing(fn($value) => Company::find($value)?->razao)
                            ->searchable()
                            ->live(),
                        Select::make('software_id')
                            ->label('Software')
                            ->placeholder('Selecione...')
                            ->options(Software::pluck('nome_software', 'id')) // Software usually few, can keep options or optimize
                            ->searchable()
                            ->live(),
                    ]),
            ])
            ->statePath('filters');
    }

    public function getViewData(): array
    {
        // Se nenhum filtro estiver aplicado, retorna lista vazia para performance
        if (empty($this->filters['empresa_id']) && empty($this->filters['software_id'])) {
            return [
                'licencas' => collect(),
            ];
        }

        $query = License::with(['software', 'company'])
            ->whereIn('status', ['ativo', 'suspenso']);

        // Aplica Filtros
        if (!empty($this->filters['empresa_id'])) {
            $query->where('empresa_codigo', $this->filters['empresa_id']);
        }

        if (!empty($this->filters['software_id'])) {
            $query->where('software_id', $this->filters['software_id']);
        }

        // Se for usuário comum ... (same comment)

        $licencas = $query->orderByDesc('data_criacao')->get();

        // Processa dados para view (badges)
        foreach ($licencas as $licenca) {
            $licenca->dados_terminais = $licenca->merged_terminals;
            $licenca->total_ativos = collect($licenca->dados_terminais)->where('ativo', 1)->count();
            // Disponíveis = Permitidos - Ativos (sem negativo)
            $licenca->total_disponiveis = max(0, $licenca->terminais_permitidos - $licenca->total_ativos);
        }

        return [
            'licencas' => $licencas,
        ];
    }

    public function disable($licencaId, $terminalId)
    {
        try {
            DB::table('terminais_software')
                ->where('licenca_id', $licencaId)
                ->where('terminal_codigo', $terminalId)
                ->update(['ativo' => 0]);

            // Recalcula utilizados na licença
            $this->recalcLicenseUsage($licencaId);

            Notification::make()->success()->title('Terminal desabilitado.')->send();
        } catch (\Exception $e) {
            Notification::make()->danger()->title('Erro ao desabilitar: ' . $e->getMessage())->send();
        }
    }

    public function remove($licencaId, $terminalId, $instalacaoId, $mac)
    {
        if ($terminalId === 'null' || $terminalId === '')
            $terminalId = null;
        if ($instalacaoId === 'null')
            $instalacaoId = null;

        // Replica a lógica complexa do legado: shieldRemoverTerminalLicenca
        try {
            DB::beginTransaction();

            // 1. Remove de licenca_instalacoes se houver
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

            // 2. Desativa em terminais_software
            if ($terminalId) {
                DB::table('terminais_software')
                    ->where('licenca_id', $licencaId)
                    ->where('terminal_codigo', $terminalId)
                    ->update(['ativo' => 0]);

                // O legado não DELETA da terminais_software, apenas desativa.
                // Mas "remove" da interface geralmente implica em resetar. 
                // O botão diz "Remover". O legado diz: `UPDATE terminais_software SET ativo = 0`.
                // Então "Remover" e "Desabilitar" fazem quase a mesma coisa no DB legado?
                // NÃO. O legado apaga de `licenca_instalacoes`. Isso libera o Slot de instalação "fantasmas".
                // E desativa o vínculo do terminal físico.
            }

            // 3. Recalcula
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
