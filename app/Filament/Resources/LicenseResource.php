<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LicenseResource\Pages;
use App\Filament\Resources\LicenseResource\RelationManagers;
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

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'Gestão de Clientes';
    protected static ?string $modelLabel = 'Licença';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados da Licença')
                    ->schema([
                        Forms\Components\Select::make('empresa_codigo')
                            ->label('Empresa')
                            ->relationship('company', 'razao')
                            ->searchable()
                            ->required()
                            ->getOptionLabelFromRecordUsing(fn($record) => "{$record->razao} ({$record->cnpj})"),

                        Forms\Components\Select::make('software_id')
                            ->label('Software')
                            ->relationship('software', 'nome_software')
                            ->required(),

                        Forms\Components\TextInput::make('serial_atual')
                            ->label('Serial Atual')
                            ->required()
                            ->maxLength(200)
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('data_expiracao')
                                    ->label('Data de Expiração')
                                    ->required()
                                    ->hidden(fn(Forms\Get $get) => $get('vitalicia')),

                                Forms\Components\Toggle::make('vitalicia')
                                    ->label('Licença Vitalícia')
                                    ->live(),

                                Forms\Components\TextInput::make('terminais_permitidos')
                                    ->label('Limite Terminais')
                                    ->numeric()
                                    ->default(1)
                                    ->required(),

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'ativo' => 'Ativo',
                                        'suspenso' => 'Suspenso',
                                        'expirado' => 'Expirado',
                                    ])
                                    ->required(),
                            ]),
                    ]),

                Forms\Components\Section::make('Informações Extras')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Textarea::make('observacoes')
                            ->label('Observações (JSON)')
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.razao')
                    ->label('Cliente')
                    ->description(function (License $record) {
                        $desc = $record->company?->cnpj ?? '';
                        if ($record->revenda) {
                            $desc .= " | Rev: {$record->revenda->razao}";
                        }
                        return $desc;
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('software.nome_software')
                    ->label('Software')
                    ->description(fn(License $record) => 'Versão: ' . $record->software?->versao)
                    ->sortable(),

                Tables\Columns\TextColumn::make('serial_atual')
                    ->label('Serial')
                    ->fontFamily('mono')
                    ->copyable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('data_expiracao')
                    ->label('Expira em')
                    ->formatStateUsing(fn(License $record) => (bool) $record->vitalicia ? 'VITALÍCIA' : ($record->data_expiracao ? $record->data_expiracao->format('d/m/Y') : '-'))
                    ->sortable()
                    ->color(fn(License $record) => (bool) $record->vitalicia ? 'success' : (\Carbon\Carbon::parse($record->data_expiracao)->isPast() ? 'danger' : 'success'))
                    ->weight(\Filament\Support\Enums\FontWeight::Bold),

                Tables\Columns\TextColumn::make('terminais')
                    ->label('Terminais')
                    ->getStateUsing(fn(License $record) => "{$record->terminais_utilizados} / {$record->terminais_permitidos}")
                    ->color(fn(License $record) => $record->terminais_utilizados >= $record->terminais_permitidos ? 'warning' : 'gray'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->getStateUsing(function (License $record) {
                        if ((bool) $record->vitalicia) {
                            return $record->status;
                        }
                        if ($record->data_expiracao && \Carbon\Carbon::parse($record->data_expiracao)->isPast()) {
                            return 'expirado';
                        }
                        return $record->status;
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'ativo' => 'success',
                        'suspenso' => 'warning',
                        'expirado' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => ucfirst($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('software_id')
                    ->label('Software')
                    ->relationship('software', 'nome_software'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'ativo' => 'Ativo',
                        'suspenso' => 'Suspenso',
                        'expirado' => 'Expirado',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->slideOver(),

                    Tables\Actions\Action::make('suspend')
                        ->label('Suspender')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->visible(fn(License $record) => $record->status === 'ativo')
                        ->action(fn(License $record) => $record->update(['status' => 'suspenso'])),

                    Tables\Actions\Action::make('activate')
                        ->label('Ativar')
                        ->icon('heroicon-o-play-circle')
                        ->color('success')
                        ->visible(fn(License $record) => $record->status !== 'ativo')
                        ->action(fn(License $record) => $record->update(['status' => 'ativo'])),

                    Tables\Actions\Action::make('viewToken')
                        ->label('Ver Token')
                        ->icon('heroicon-o-shield-check')
                        ->color('info')
                        ->modalHeading('Token de Licenciamento')
                        ->modalContent(fn(License $record) => view('filament.modals.view-license-token', [
                            'token' => json_decode($record->observacoes, true)['token'] ?? 'Nenhum token encontrado.'
                        ]))
                        ->modalSubmitAction(false),

                    Tables\Actions\Action::make('generateOfflineToken')
                        ->label('Gerar Token Offline')
                        ->icon('heroicon-o-qr-code')
                        ->color('gray')
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

                            $token = $service->generateOfflineSignedToken($payload);

                            // Exibe o token em um Modal "fake" ou Notification
                            \Filament\Notifications\Notification::make()
                                ->title('Token Gerado')
                                ->body(new \Illuminate\Support\HtmlString("Copie o token abaixo:<br><br><code style='user-select:all; background:#f3f4f6; padding:5px; border-radius:4px; word-break:break-all;'>{$token}</code>"))
                                ->persistent()
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        return [
            LicenseResource\Widgets\LicenseOverview::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLicenses::route('/'),
            'create' => Pages\CreateLicense::route('/create'),
            'edit' => Pages\EditLicense::route('/{record}/edit'),
        ];
    }
}
