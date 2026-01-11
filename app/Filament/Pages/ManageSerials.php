<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Models\Software;
use App\Models\SerialHistory;
use App\Models\License;
use App\Services\SerialNumberService;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action as TableAction;
use Illuminate\Database\Eloquent\Builder;

class ManageSerials extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationLabel = 'Gerenciamento de Seriais';
    protected static ?string $title = 'Gerenciamento Avançado de Seriais';
    protected static ?string $navigationGroup = 'Gestão de Clientes';
    protected static ?int $navigationSort = 4;
    protected static string $view = 'filament.pages.manage-serials';

    public ?array $generatorData = [];
    public ?array $validatorData = [];
    public ?string $validationResult = null;
    public ?array $validationDetails = null;
    public ?array $historyFilterData = [];
    public ?string $generatedSerial = null;
    public ?string $generatedToken = null;

    public function mount(): void
    {
        $this->generatorForm->fill([
            'validade_dias' => 30,
            'terminais' => 1
        ]);
        $this->validatorForm->fill();
        $this->historyFilterForm->fill();
    }

    protected function getForms(): array
    {
        return [
            'generatorForm',
            'validatorForm',
            'historyFilterForm',
        ];
    }

    public function generatorForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Gerar Novo Serial')
                    ->icon('heroicon-o-plus-circle')
                    ->schema([
                        Select::make('empresa_id')
                            ->label('Empresa')
                            ->placeholder('Digite para buscar...')
                            ->getSearchResultsUsing(fn(string $search) => Company::where('razao', 'like', "%{$search}%")->orWhere('cnpj', 'like', "%{$search}%")->limit(50)->pluck('razao', 'codigo'))
                            ->getOptionLabelUsing(fn($value) => Company::find($value)?->razao)
                            ->searchable()
                            ->required(),
                        Select::make('software_id')
                            ->label('Software')
                            ->placeholder('Selecione um software...')
                            ->options(Software::pluck('nome_software', 'id'))
                            ->searchable()
                            ->required(),
                        Select::make('cnpj_revenda')
                            ->label('Revenda (Opcional)')
                            ->placeholder('Digite para buscar revenda...')
                            ->getSearchResultsUsing(fn(string $search) => Company::whereHas('users', fn($q) => $q->where('acesso', 2))->where(fn($q) => $q->where('razao', 'like', "%{$search}%")->orWhere('cnpj', 'like', "%{$search}%"))->limit(50)->pluck('razao', 'cnpj'))
                            ->getOptionLabelUsing(fn($value) => Company::where('cnpj', $value)->first()?->razao)
                            ->searchable(),
                        Grid::make(2)->schema([
                            TextInput::make('validade_dias')
                                ->label('Validade (dias)')
                                ->numeric()
                                ->default(30)
                                ->required(),
                            TextInput::make('terminais')
                                ->label('Nº Terminais')
                                ->numeric()
                                ->default(1)
                                ->required(),
                        ]),
                        \Filament\Forms\Components\Actions::make([
                            Action::make('gerar')
                                ->label('Gerar Serial')
                                ->icon('heroicon-o-bolt')
                                ->color('primary')
                                ->action('generateSerial'),
                        ]),
                    ])
                    ->statePath('generatorData'),
            ]);
    }

    public function validatorForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Validar Serial')
                    ->icon('heroicon-o-check-badge')
                    ->schema([
                        TextInput::make('serial')
                            ->label('Serial')
                            ->placeholder('Digite o serial completo...')
                            ->required(),
                        Textarea::make('token')
                            ->label('Token (Opcional)')
                            ->placeholder('Cole o token JSON...'),
                        \Filament\Forms\Components\Actions::make([
                            Action::make('validar')
                                ->label('Validar Serial')
                                ->icon('heroicon-o-magnifying-glass')
                                ->color('info')
                                ->action('validateSerial'),
                        ]),
                    ])
                    ->statePath('validatorData'),
            ]);
    }

    public function historyFilterForm(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(1)->schema([
                    Select::make('empresa_cod')
                        ->label('Empresa')
                        ->placeholder('Digite para pesquisar uma empresa e ver o histórico...')
                        ->getSearchResultsUsing(fn(string $search) => Company::where('razao', 'like', "%{$search}%")->orWhere('cnpj', 'like', "%{$search}%")->limit(50)->pluck('razao', 'codigo'))
                        ->getOptionLabelUsing(fn($value) => Company::find($value)?->razao)
                        ->searchable()
                        ->live(debounce: 500)
                        ->afterStateUpdated(function () {
                            $this->resetTable();
                        }),
                ]),
            ])
            ->statePath('historyFilterData');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $empresaCod = $this->historyFilterData['empresa_cod'] ?? null;

                if ($empresaCod) {
                    return SerialHistory::query()
                        ->where('empresa_codigo', $empresaCod)
                        ->latest('data_geracao');
                }

                return SerialHistory::query()->whereRaw('1 = 0');
            })
            ->columns([
                TextColumn::make('software.nome_software')
                    ->label('Software')
                    ->searchable(),
                TextColumn::make('serial_gerado')
                    ->label('Serial')
                    ->fontFamily('mono')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('data_geracao')
                    ->label('Gerado em')
                    ->dateTime('d/m/Y H:i'),
                TextColumn::make('validade_licenca')
                    ->label('Validade')
                    ->date('d/m/Y')
                    ->color(fn($record) => \Carbon\Carbon::parse($record->validade_licenca)->isPast() ? 'danger' : 'success'),
                TextColumn::make('status')
                    ->label('Status')
                    ->state(function (SerialHistory $record): string {
                        if ($record->validade_licenca && \Carbon\Carbon::parse($record->validade_licenca)->lt(now()->startOfDay())) {
                            return 'Expirado';
                        }
                        $obs = json_decode($record->observacoes, true);
                        if (($obs['status_inativacao'] ?? '') === 'renovado') {
                            return 'Renovado';
                        }
                        if ($record->ativo) {
                            return 'Ativo';
                        }
                        return 'Desativado';
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Ativo' => 'success',
                        'Renovado' => 'info',
                        'Expirado' => 'warning',
                        'Desativado' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->actions([
                // Botão Ativar/Desativar (Bloqueado se Renovado)
                TableAction::make('toggle')
                    ->label(function ($record) {
                        $obs = json_decode($record->observacoes, true);
                        if (($obs['status_inativacao'] ?? '') === 'renovado') {
                            return 'Renovado';
                        }
                        return $record->ativo ? 'Desativar' : 'Ativar';
                    })
                    ->color(function ($record) {
                        $obs = json_decode($record->observacoes, true);
                        if (($obs['status_inativacao'] ?? '') === 'renovado') {
                            return 'gray';
                        }
                        return $record->ativo ? 'warning' : 'success';
                    })
                    ->icon(function ($record) {
                        $obs = json_decode($record->observacoes, true);
                        if (($obs['status_inativacao'] ?? '') === 'renovado') {
                            return 'heroicon-o-lock-closed';
                        }
                        return $record->ativo ? 'heroicon-o-no-symbol' : 'heroicon-o-check-circle';
                    })
                    ->disabled(function ($record) {
                        $obs = json_decode($record->observacoes, true);
                        return ($obs['status_inativacao'] ?? '') === 'renovado';
                    })
                    ->action(function ($record) {
                        $record->update(['ativo' => !$record->ativo]);
                        Notification::make()->success()->title('Status atualizado')->send();
                    }),

                // Botão Terminais
                TableAction::make('terminais')
                    ->label('Terminais')
                    ->icon('heroicon-o-computer-desktop')
                    ->color('info')
                    ->modalHeading('Gerenciar Terminais Vinculados')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->modalContent(fn($record) => view('filament.pages.modals.serial-terminals', ['record' => $record])),

                // Botão Token
                TableAction::make('token')
                    ->label('Token')
                    ->icon('heroicon-o-clipboard-document')
                    ->color('gray')
                    ->modalHeading('Token de Ativação')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->modalContent(fn($record) => view('filament.pages.modals.serial-token', ['record' => $record]))
            ]);
    }

    public function generateSerial()
    {
        $data = $this->generatorForm->getState();

        if (!isset($data['empresa_id']))
            $data['empresa_id'] = $this->generatorData['empresa_id'] ?? null;
        if (!isset($data['software_id']))
            $data['software_id'] = $this->generatorData['software_id'] ?? null;
        if (!isset($data['validade_dias']))
            $data['validade_dias'] = $this->generatorData['validade_dias'] ?? 30;
        if (!isset($data['terminais']))
            $data['terminais'] = $this->generatorData['terminais'] ?? 1;
        if (!isset($data['cnpj_revenda']))
            $data['cnpj_revenda'] = $this->generatorData['cnpj_revenda'] ?? null;

        try {
            if (empty($data['empresa_id'])) {
                throw new \Exception('Selecione uma empresa válida.');
            }
            if (empty($data['software_id'])) {
                throw new \Exception('Selecione um software válido.');
            }

            $service = new SerialNumberService();
            $result = $service->generate(
                (int) $data['empresa_id'],
                (int) $data['software_id'],
                (int) $data['validade_dias'],
                (int) $data['terminais'],
                $data['cnpj_revenda']
            );

            $this->generatedSerial = $result['serial'];
            $this->generatedToken = $result['token'];

            Notification::make()->success()->title('Serial gerado com sucesso!')->send();
        } catch (\Exception $e) {
            Notification::make()->danger()->title('Erro ao gerar: ' . $e->getMessage())->send();
        }
    }

    public function validateSerial()
    {
        $data = $this->validatorForm->getState();
        $serial = trim($data['serial'] ?? $this->validatorData['serial'] ?? '');

        if (empty($serial)) {
            Notification::make()->warning()->title('Por favor, informe o serial para consulta.')->send();
            return;
        }

        $history = SerialHistory::where('serial_gerado', $serial)->latest()->first();

        if ($history) {
            $this->validationResult = 'valid';
            $this->validationDetails = [
                'Empresa' => $history->company->razao ?? 'N/A',
                'Software' => $history->software->nome_software ?? 'N/A',
                'Status' => $history->ativo ? 'Ativo' : 'Inativo/Suspenso',
                'Validade' => $history->validade_licenca ? \Carbon\Carbon::parse($history->validade_licenca)->format('d/m/Y') : 'N/A',
            ];
        } else {
            $this->validationResult = 'invalid';
            $this->validationDetails = ['Erro' => 'Serial não encontrado na base de dados.'];
        }
    }

    // Método Global para remover terminal/instalação
    public function removeTerminal($licencaId, $terminalCodigo, $instalacaoId = null, $mac = null)
    {
        // Tratamento de tipos que vêm do Livewire como string 'null' ou vazio
        if ($terminalCodigo === 'null' || $terminalCodigo === '')
            $terminalCodigo = null;
        if ($instalacaoId === 'null' || $instalacaoId === '')
            $instalacaoId = null;
        if ($mac === 'null' || $mac === '')
            $mac = null;

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // 1. Remove de licenca_instalacoes (pendentes/fantasmas)
            if ($instalacaoId) {
                \Illuminate\Support\Facades\DB::table('licenca_instalacoes')
                    ->where('licenca_id', $licencaId)
                    ->where('instalacao_id', $instalacaoId)
                    ->delete();
            }
            if ($mac) {
                \Illuminate\Support\Facades\DB::table('licenca_instalacoes')
                    ->where('licenca_id', $licencaId)
                    ->where('mac_address', $mac)
                    ->delete();
            }

            // 2. Desativa em terminais_software (vínculo físico)
            if ($terminalCodigo) {
                \Illuminate\Support\Facades\DB::table('terminais_software')
                    ->where('licenca_id', $licencaId)
                    ->where('terminal_codigo', $terminalCodigo)
                    ->update(['ativo' => 0]);
            }

            // 3. Recalcula utilizados
            $utilizados = \Illuminate\Support\Facades\DB::table('terminais_software')
                ->where('licenca_id', $licencaId)
                ->where('ativo', 1)
                ->count();

            $instalacoes = \Illuminate\Support\Facades\DB::table('licenca_instalacoes')
                ->where('licenca_id', $licencaId)
                ->count();

            $final = max($utilizados, $instalacoes);

            License::where('id', $licencaId)->update(['terminais_utilizados' => $final]);

            \Illuminate\Support\Facades\DB::commit();

            Notification::make()->success()->title('Terminal/Instalação removido com sucesso.')->send();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            Notification::make()->danger()->title('Erro ao remover: ' . $e->getMessage())->send();
        }
    }
}
