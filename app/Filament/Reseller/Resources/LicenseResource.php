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
                            ->required(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.razao')
                    ->label('Cliente')
                    ->description(fn(License $record) => (function ($doc) {
                        $doc = preg_replace('/\D/', '', $doc ?? '');
                        if (strlen($doc) <= 11 && strlen($doc) > 0) {
                            return substr($doc, 0, 3) . '.***.***-' . substr($doc, -2);
                        }
                        return $record->company?->cnpj ?? '-';
                    })($record->company?->cnpj))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('software.nome_software')
                    ->label('Software')
                    ->description(fn(License $record) => 'v' . ($record->software?->versao ?? '?'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('data_expiracao')
                    ->label('Validade')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn(License $record) => \Carbon\Carbon::parse($record->data_expiracao)->isPast() ? 'danger' : (\Carbon\Carbon::parse($record->data_expiracao)->diffInDays(now()) <= 15 ? 'warning' : 'success'))
                    ->weight('bold'),

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

                            \Illuminate\Support\Facades\DB::table('revenda_transacoes')->insert([
                                'usuario_id' => $user->id,
                                'tipo' => 'debito',
                                'valor' => $valor,
                                'saldo_anterior' => $saldoAnterior,
                                'saldo_novo' => $saldoNovo,
                                'descricao' => "Renovação Licença #{$record->id} - {$record->software->nome_software} ({$plano->nome_plano})",
                                'data_transacao' => now(),
                            ]);

                            $validadeAtual = \Carbon\Carbon::parse($record->data_expiracao);
                            $novaValidade = $validadeAtual->isFuture() ? $validadeAtual : now();

                            $recorrencia = \Illuminate\Support\Str::slug($plano->recorrencia);
                            if (str_contains($recorrencia, 'anual')) {
                                $novaValidade->addYear();
                            } else {
                                $novaValidade->addMonth();
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
                    ->tooltip('Copiar Token')
                    ->action(fn($record) => \Filament\Notifications\Notification::make()->title('Token copiado!')->body($record->serial_atual)->success()->send()),
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
