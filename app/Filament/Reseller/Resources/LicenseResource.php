<?php

namespace App\Filament\Reseller\Resources;

use App\Filament\Reseller\Resources\LicenseResource\Pages;
use App\Filament\Reseller\Resources\LicenseResource\RelationManagers;
use App\Models\License;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LicenseResource extends Resource
{
    protected static ?string $model = License::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';
    protected static ?string $modelLabel = 'Licença';
    protected static ?string $pluralModelLabel = 'Gerenciamento de Licenças';
    protected static ?string $navigationLabel = 'Licenças';
    protected static ?string $navigationGroup = 'Gestão de Clientes';
    protected static ?int $navigationSort = 2;

    // Ensure reseller sees only their licenses
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('cnpj_revenda', auth()->user()->cnpj);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalhes da Licença')
                    ->schema([
                        Forms\Components\TextInput::make('company.razao')
                            ->label('Cliente')
                            ->disabled(),
                        Forms\Components\TextInput::make('software.nome_software')
                            ->label('Software')
                            ->disabled(),
                        Forms\Components\DatePicker::make('data_expiracao')
                            ->label('Nova Validade')
                            ->required()
                            ->hidden(fn($record) => $record?->vitalicia ?? false),

                        Forms\Components\Placeholder::make('lbl_vitalicia')
                            ->label('Validade')
                            ->content('Licença Vitalícia')
                            ->visible(fn($record) => $record?->vitalicia ?? false),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('company.razao')
                    ->label('Cliente')
                    ->description(fn(License $record) => new \Illuminate\Support\HtmlString((function ($doc) use ($record) {
                        $doc = preg_replace('/\D/', '', $doc ?? '');
                        $masked = $record->company?->cnpj ?? '-';
                        if (strlen($doc) <= 11 && strlen($doc) > 0) {
                            $masked = substr($doc, 0, 3) . '.***.***-' . substr($doc, -2);
                        }

                        $idDisplay = $record->company?->codigo ? "ID: {$record->company->codigo}" : '';
                        $emailDisplay = $record->company?->email ? "{$record->company->email}" : '';

                        $extraInfo = [];
                        if ($idDisplay)
                            $extraInfo[] = $idDisplay;
                        if ($emailDisplay)
                            $extraInfo[] = $emailDisplay;

                        $extraHtml = !empty($extraInfo) ? "<br><span style='font-size: 0.8em; opacity: 0.8;'>" . implode(' | ', $extraInfo) . "</span>" : "";

                        return $masked . $extraHtml;
                    })($record->company?->cnpj)))
                    ->searchable(query: function (\Illuminate\Database\Eloquent\Builder $query, string $search): \Illuminate\Database\Eloquent\Builder {
                        return $query->whereHas('company', function ($q) use ($search) {
                            $q->where('razao', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('cnpj', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('software.nome_software')
                    ->label('Software')
                    ->description(fn(License $record) => 'v' . ($record->software?->versao ?? '?'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('data_expiracao')
                    ->label('Validade')
                    ->formatStateUsing(fn(License $record) => (bool) $record->vitalicia ? 'VITALÍCIA' : ($record->data_expiracao ? $record->data_expiracao->format('d/m/Y') : '-'))
                    ->sortable()
                    ->color(fn(License $record) => match (true) {
                        (bool) $record->vitalicia => 'success',
                        !$record->data_expiracao => 'gray', // Sem data
                        $record->data_expiracao->isPast() => 'danger', // Vencido (Vermelho)
                        $record->data_expiracao->lte(now()->addDays(15)) => 'warning', // Vence em <= 15 dias (Laranja)
                        default => 'success', // Válido (Verde)
                    })
                    ->weight(\Filament\Support\Enums\FontWeight::Bold),

                Tables\Columns\TextColumn::make('terminais')
                    ->label('Terminais')
                    ->alignCenter()
                    ->getStateUsing(fn(License $record) => "{$record->terminais_utilizados} / {$record->terminais_permitidos}"),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function (License $record) {
                        if ($record->data_expiracao && \Carbon\Carbon::parse($record->data_expiracao)->isPast()) {
                            return 'expirado';
                        }
                        return $record->status ? 'ativo' : 'inativo'; // Assuming boolean status
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'ativo' => 'success',
                        'suspenso' => 'warning',
                        'expirado' => 'danger',
                        'inativo' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state) => ucfirst($state))
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'ativo' => 'Ativo',
                        'suspenso' => 'Suspenso',
                        'expirado' => 'Expirado',
                    ])
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'];
                        if ($value === 'ativo') {
                            $query->where(function ($q) {
                                $q->where('status', true)
                                    ->orWhere('status', 'ativo')
                                    ->orWhere('status', 1);
                            })->whereDate('data_expiracao', '>=', now());
                        } elseif ($value === 'expirado') {
                            $query->whereDate('data_expiracao', '<', now());
                            // Opcionalmente incluir status 'expirado' se existir no banco
                        } elseif ($value === 'suspenso') {
                            $query->where('status', 'suspenso'); // ou o que for usado para suspenso
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('renovar')
                    ->label('')
                    ->icon('heroicon-m-arrow-path')
                    ->color('primary')
                    ->button()
                    ->extraAttributes(['class' => 'force-btn-height'])
                    ->tooltip('Renovar Licença')
                    ->modalHeading('Renovar Licença')
                    ->modalDescription(fn(License $record) => "Confirma a renovação da licença do software {$record->software->nome_software}?")
                    ->form(function (License $record) {
                        $planos = \App\Models\Plano::where('software_id', $record->software_id)
                            ->where(function ($query) {
                                $query->where('status', 'ativo')
                                    ->orWhere('status', '1')
                                    ->orWhere('status', true);
                            })
                            ->get();

                        if ($planos->isEmpty()) {
                            return [
                                Forms\Components\Placeholder::make('erro_plano')
                                    ->content('Nenhum plano ativo encontrado para este software (ID: ' . $record->software_id . '). Contate o suporte.')
                                    ->textHtml(),
                            ];
                        }

                        return [
                            Forms\Components\Select::make('plano_id')
                                ->label('Plano de Renovação')
                                ->options($planos->mapWithKeys(fn($p) => [$p->id => $p->nome_plano . ' - R$ ' . number_format($p->valor, 2, ',', '.')]))
                                ->default($planos->first()->id)
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(fn($state, Forms\Set $set) => $set('custo_display', 'R$ ' . number_format(\App\Models\Plano::find($state)?->valor ?? 0, 2, ',', '.'))),

                            Forms\Components\TextInput::make('custo_display')
                                ->label('Custo')
                                ->disabled()
                                ->dehydrated(false)
                                ->default('R$ ' . number_format($planos->first()->valor, 2, ',', '.')),
                        ];
                    })
                    ->action(function (array $data, License $record) {
                        if (!isset($data['plano_id'])) {
                            \Filament\Notifications\Notification::make()->title('Erro: Plano não selecionado')->danger()->send();
                            return;
                        }

                        $plano = \App\Models\Plano::find($data['plano_id']);
                        $valor = $plano->valor;
                        $user = auth()->user();
                        $empresaRevenda = $user->empresa;

                        if (!$empresaRevenda) {
                            \Filament\Notifications\Notification::make()->title('Erro: Cadastro de empresa revendedora não encontrado.')->danger()->send();
                            return;
                        }

                        if ($empresaRevenda->saldo < $valor) {
                            \Filament\Notifications\Notification::make()
                                ->title('Saldo Insuficiente')
                                ->body("O custo é R$ " . number_format($valor, 2, ',', '.') . ". Seu saldo atual é R$ " . number_format($empresaRevenda->saldo, 2, ',', '.') . ".")
                                ->danger()
                                ->send();
                            return;
                        }

                        \Illuminate\Support\Facades\DB::transaction(function () use ($empresaRevenda, $valor, $user, $record, $plano) {
                            $saldoAnterior = $empresaRevenda->saldo;
                            $saldoNovo = $saldoAnterior - $valor;
                            $empresaRevenda->forceFill(['saldo' => $saldoNovo])->save();

                            \App\Models\CreditHistory::create([
                                'empresa_cnpj' => preg_replace('/\D/', '', $empresaRevenda->cnpj),
                                'usuario_id' => $user->id,
                                'tipo' => 'saida',
                                'valor' => $valor,
                                'descricao' => "Renovação Licença #{$record->id} - {$record->software->nome_software} ({$plano->nome_plano})",
                                'data_movimento' => now(),
                            ]);

                            // Gerar Pedido para contabilidade/receita
                            \App\Models\Order::create([
                                'user_id' => $user->id,
                                'plano_id' => $plano->id,
                                'cnpj_revenda' => preg_replace('/\D/', '', $empresaRevenda->cnpj),
                                'valor' => $valor,
                                'total' => $valor,
                                'status' => 'paid',
                                // 'paid_at' => now(), // Removido pois a coluna não existe no banco
                                'recorrencia' => 'RENOVACAO',
                                'licenca_id' => $record->id,
                                'payment_method' => 'SALDO',
                                'asaas_payment_id' => 'SALDO-' . time(), // Identificador interno para compatibilidade
                                // 'condicao' => 'A VISTA', // Removido
                                // 'forma_pagamento' => 'SALDO REVENDA' // Removido
                            ]);

                            $validadeAtual = \Carbon\Carbon::parse($record->data_expiracao);
                            $novaValidade = $validadeAtual->isFuture() ? $validadeAtual : now();

                            // Prioridade: Valor Numérico Inteiro (Meses)
                            $mesesRecorrencia = (int) $plano->recorrencia;

                            if ($mesesRecorrencia > 0) {
                                $novaValidade->addMonths($mesesRecorrencia);
                            } else {
                                // Fallback: Tenta extrair número da string ou analisar texto
                                preg_match('/(\d+)/', $plano->recorrencia, $matches);
                                $mesesExtract = isset($matches[1]) ? (int) $matches[1] : 0;

                                if ($mesesExtract > 0) {
                                    $novaValidade->addMonths($mesesExtract);
                                } else {
                                    $recorrenciaStr = \Illuminate\Support\Str::slug($plano->recorrencia);
                                    if (str_contains($recorrenciaStr, 'anual')) {
                                        $novaValidade->addYear();
                                    } elseif (str_contains($recorrenciaStr, 'semestral')) {
                                        $novaValidade->addMonths(6);
                                    } elseif (str_contains($recorrenciaStr, 'trimestral')) {
                                        $novaValidade->addMonths(3);
                                    } else {
                                        $novaValidade->addMonth();
                                    }
                                }
                            }

                            $record->update([
                                'data_expiracao' => $novaValidade,
                                'data_ultima_renovacao' => now(),
                                'status' => 'ativo'
                            ]);
                        });

                        \Filament\Notifications\Notification::make()->title('Licença Renovada com Sucesso!')->success()->send();
                    }),

                Tables\Actions\Action::make('instalações')
                    ->label('')
                    ->icon('heroicon-m-computer-desktop')
                    ->color('info')
                    ->button()
                    ->extraAttributes(['class' => 'force-btn-height'])
                    ->tooltip('Visualizar Terminais')
                    ->modalHeading('Terminais da Licença')
                    ->modalContent(fn($record) => view('filament.reseller.modals.license-terminals-wrapper', [
                        'licenseId' => $record->id
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar'),

                Tables\Actions\Action::make('token')
                    ->label('')
                    ->icon('heroicon-m-clipboard')
                    ->color('warning')
                    ->button()
                    ->extraAttributes(['class' => 'force-btn-height'])
                    ->tooltip('Copiar Serial')
                    ->action(fn($record) => \Filament\Notifications\Notification::make()->title('Serial copiado!')->body($record->serial_atual)->success()->send()),

                Tables\Actions\Action::make('generateOfflineToken')
                    ->label('')
                    ->icon('heroicon-m-qr-code')
                    ->color('gray')
                    ->button()
                    ->extraAttributes(['class' => 'force-btn-height'])
                    ->tooltip('Gerar Token Offline')
                    ->form([
                        Forms\Components\TextInput::make('instalacao_id')
                            ->label('Código de Instalação (Hardware ID)')
                            ->helperText('Cole o código que o cliente enviou.')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (array $data, License $record, \App\Services\LicenseService $service) {
                        $payload = [
                            'serial' => $record->serial_atual,
                            'empresa_codigo' => $record->empresa_codigo,
                            'software_id' => $record->software_id,
                            'instalacao_id' => $data['instalacao_id'],
                            'terminais' => $record->terminais_permitidos,
                            'validade' => $record->data_expiracao ? $record->data_expiracao->format('Y-m-d') : null,
                            'emitido_em' => now()->toIso8601String(),
                            'modo' => 'offline_manual'
                        ];

                        if ($record->vitalicia) {
                            unset($payload['validade']);
                            $payload['vitalicia'] = true;
                        }

                        // 1. Busca a chave de ativação offline do software (pelo escopo)
                        $apiKey = \App\Models\ApiKey::where('software_id', $record->software_id)
                            ->where('status', 'ativo')
                            ->get()
                            ->filter(function ($k) {
                            $scopes = $k->scopes;
                            if (is_string($scopes))
                                $scopes = json_decode($scopes, true) ?? [];
                            return in_array('offline_activation', $scopes);
                        })
                            ->first();

                        if (!$apiKey) {
                            \Filament\Notifications\Notification::make()
                                ->title('Erro de Configuração')
                                ->body('Não foi encontrada uma API Key Ativa com o escopo "offline_activation" para este software.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $token = $service->generateOfflineSignedToken($payload, $apiKey->key_hash);

                        \Filament\Notifications\Notification::make()
                            ->title('Token Gerado')
                            ->body(new \Illuminate\Support\HtmlString("Copie o token abaixo:<br><br><code style='user-select:all; background:#f3f4f6; padding:5px; border-radius:4px; word-break:break-all;'>{$token}</code>"))
                            ->persistent()
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLicenses::route('/'),
            'create' => Pages\CreateLicense::route('/create'),
            // 'edit' => Pages\EditLicense::route('/{record}/edit'), // Removido para evitar edição direta
        ];
    }
}
