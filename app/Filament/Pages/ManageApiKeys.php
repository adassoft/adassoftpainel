<?php

namespace App\Filament\Pages;

use App\Models\ApiKey;
use App\Models\Company;
use App\Models\Software;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ManageApiKeys extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationLabel = 'Gestão de API Keys';
    protected static ?string $title = 'Gestão de API Keys';
    protected static ?string $navigationGroup = 'Configurações';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.manage-api-keys';

    public ?array $data = [];
    public ?string $generatedKey = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('sync_software_keys')
                ->label('Importar Chaves dos Softwares')
                ->icon('heroicon-o-arrow-down-on-square-stack')
                ->color('primary')
                ->action(function () {
                    $softwares = Software::whereNotNull('api_key_hash')->get();
                    $count = 0;
                    foreach ($softwares as $sw) {
                        // Verifica se já existe uma chave com este hash
                        if (!ApiKey::where('key_hash', $sw->api_key_hash)->exists()) {
                            ApiKey::create([
                                'software_id' => $sw->id,
                                'label' => 'Chave Original do Software',
                                'key_hash' => $sw->api_key_hash,
                                'key_hint' => $sw->api_key_hint ?? '????',
                                'scopes' => ['*'],
                                'status' => 'ativo',
                                'created_by' => auth()->id() ?? 1
                            ]);
                            $count++;
                        }
                    }
                    if ($count > 0)
                        Notification::make()->success()->title("$count chaves importadas.")->send();
                    else
                        Notification::make()->info()->title("Nenhuma chave nova encontrada.")->send();
                })
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('software_id')
                    ->label('Software')
                    ->options(\App\Models\Software::all()->mapWithKeys(fn($sw) => [$sw->id => "{$sw->nome_software} (ID: {$sw->id})"]))
                    ->searchable()
                    ->required(),

                TextInput::make('label')
                    ->label('Descrição')
                    ->placeholder('Ex: Integração ERP, Parceiro X')
                    ->maxLength(255),

                Select::make('empresa_codigo')
                    ->label('Empresa (Opcional)')
                    ->options(Company::pluck('razao', 'codigo'))
                    ->searchable()
                    ->placeholder('Selecione ou deixe vazio'),

                CheckboxList::make('scopes')
                    ->label('Escopos Permitidos')
                    ->options([
                        'emitir_token' => 'Emitir Token',
                        'validar_serial' => 'Validar Serial',
                        'status_licenca' => 'Status Licença',
                        'listar_terminais' => 'Listar Terminais',
                        'remover_terminal' => 'Remover Terminal',
                        'offline_activation' => 'Ativação Offline', // Para tokens que o Delphi valida
                        'online_activation' => 'Ativação Online',   // Para tokens de sessão do Servidor
                    ])
                    ->columns(2)
                    ->default(array_keys([
                        'emitir_token' => 1,
                        'validar_serial' => 1,
                        'status_licenca' => 1,
                        'listar_terminais' => 1,
                        'remover_terminal' => 1,
                        'offline_activation' => 1,
                        'online_activation' => 1
                    ])), // Padrão todos? Ou vazio? Usuário disse "padrão todos" no legado.

                DateTimePicker::make('expires_at')
                    ->label('Expira em (Opcional)'),

                \Filament\Forms\Components\Actions::make([
                    FormAction::make('create')
                        ->label('Gerar API Key')
                        ->icon('heroicon-o-key')
                        ->color('success')
                        ->action('createKey'),
                ]),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ApiKey::query()->latest())
            ->columns([
                TextColumn::make('id')->sortable()->label('ID'),

                TextColumn::make('software.nome_software')
                    ->description(fn(ApiKey $record) => "ID: {$record->software_id} | " . $record->label)
                    ->label('Software (ID)')
                    ->searchable(),

                TextColumn::make('key_hint')
                    ->label('Hint')
                    ->formatStateUsing(fn($state) => '****' . $state)
                    ->fontFamily('mono'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'ativo' => 'success',
                        'revogado' => 'danger',
                        'inativo' => 'warning',
                    }),

                TextColumn::make('scopes')
                    ->badge()
                    ->color('info')
                    ->limitList(3)
                    ->separator(','),

                TextColumn::make('expires_at')
                    ->label('Expira')
                    ->dateTime('d/m/Y')
                    ->placeholder('Sem expiração'),

                TextColumn::make('use_count')
                    ->label('Usos')
                    ->alignCenter(),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('rotate')
                        ->label('Rotacionar')
                        ->icon('heroicon-o-arrow-path')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalHeading('Rotacionar Chave')
                        ->modalDescription('Isso irá gerar uma nova chave com as mesmas permissões e REVOGAR a chave atual. O sistema que usa a chave antiga parará de funcionar até ser atualizado.')
                        ->action(function (ApiKey $record) {
                            $this->rotateKey($record);
                        }),

                    Action::make('toggle_status')
                        ->label(fn($record) => $record->status === 'ativo' ? 'Inativar' : 'Ativar')
                        ->icon(fn($record) => $record->status === 'ativo' ? 'heroicon-o-pause' : 'heroicon-o-play')
                        ->color(fn($record) => $record->status === 'ativo' ? 'warning' : 'success')
                        ->visible(fn($record) => $record->status !== 'revogado')
                        ->action(function (ApiKey $record) {
                            $novo = $record->status === 'ativo' ? 'inativo' : 'ativo';
                            $record->update(['status' => $novo]);
                            Notification::make()->success()->title("Status alterado para {$novo}")->send();
                        }),

                    Action::make('revoke')
                        ->label('Revogar')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn($record) => $record->status !== 'revogado')
                        ->action(function (ApiKey $record) {
                            $record->update(['status' => 'revogado']);
                            Notification::make()->success()->title('Chave revogada.')->send();
                        }),

                    Action::make('view_offline_secret')
                        ->label('Ver Segredo (Hash)')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->visible(
                            fn(ApiKey $record) =>
                            is_array($record->scopes) && (
                                in_array('offline_activation', $record->scopes) ||
                                in_array('online_activation', $record->scopes)
                            )
                        )
                        ->modalHeading('Segredo para Validação (Hash SHA-256)')
                        ->modalDescription('Copie este valor para configurar a validação de assinatura no cliente (Delphi/App).')
                        ->modalContent(fn(ApiKey $record) => new \Illuminate\Support\HtmlString("
                            <div class='p-4 bg-gray-100 rounded border'>
                                <p class='text-sm text-gray-500 mb-2'>Hash SHA-256 (Use este como segredo):</p>
                                <code class='break-all select-all font-mono text-lg'>{$record->key_hash}</code>
                            </div>
                        "))
                        ->modalSubmitAction(false) // View only
                        ->modalCancelActionLabel('Fechar'),

                    Action::make('delete')
                        ->label('Excluir')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (ApiKey $record) {
                            $record->delete();
                            Notification::make()->success()->title('Chave excluída.')->send();
                        }),
                ])
            ])
            ->poll('10s');
    }

    public function createKey()
    {
        $data = $this->form->getState();

        try {
            DB::beginTransaction();

            $rawKey = bin2hex(random_bytes(32)); // 64 chars
            $hash = hash('sha256', $rawKey);
            $hint = substr($rawKey, -4);

            // Garantir escopos únicos e array simples
            $scopes = array_values(array_unique($data['scopes'] ?? []));
            if (empty($scopes))
                $scopes = ['*']; // Fallback

            ApiKey::create([
                'software_id' => $data['software_id'],
                'empresa_codigo' => $data['empresa_codigo'] ?? null,
                'label' => $data['label'],
                'key_hash' => $hash,
                'key_hint' => $hint,
                'scopes' => $scopes,
                'expires_at' => $data['expires_at'],
                'created_by' => auth()->id(),
                'status' => 'ativo',
            ]);

            DB::commit();

            $this->generatedKey = $rawKey;
            $this->form->fill(); // Reset form

            Notification::make()->success()->title('API Key criada com sucesso!')->send();

        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()->danger()->title('Erro ao criar chave: ' . $e->getMessage())->send();
        }
    }

    public function rotateKey(ApiKey $oldKey)
    {
        try {
            DB::beginTransaction();

            // Revoga antiga
            $oldKey->update(['status' => 'revogado']);

            // Gera nova baseada na antiga
            $rawKey = bin2hex(random_bytes(32));
            $hash = hash('sha256', $rawKey);
            $hint = substr($rawKey, -4);

            ApiKey::create([
                'software_id' => $oldKey->software_id,
                'empresa_codigo' => $oldKey->empresa_codigo,
                'label' => $oldKey->label,
                'key_hash' => $hash,
                'key_hint' => $hint,
                'scopes' => $oldKey->scopes,
                'expires_at' => $oldKey->expires_at,
                'created_by' => auth()->id(),
                'status' => 'ativo',
            ]);

            DB::commit();

            $this->generatedKey = $rawKey; // Exibe a nova chave

            Notification::make()->success()->title('Chave rotacionada com sucesso!')->send();

        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()->danger()->title('Erro na rotação: ' . $e->getMessage())->send();
        }
    }
}
