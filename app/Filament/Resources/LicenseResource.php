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
    protected static ?string $modelLabel = 'Licença';
    protected static ?string $pluralModelLabel = 'Gestão de Licenças';
    protected static ?string $navigationGroup = 'Licenciamento e Ativações';
    protected static ?int $navigationSort = 1;

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
                                    ->required(),

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
                    ->description(fn(License $record) => $record->company?->cnpj)
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
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn(License $record) => \Carbon\Carbon::parse($record->data_expiracao)->isPast() ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('terminais')
                    ->label('Terminais')
                    ->getStateUsing(fn(License $record) => "{$record->terminais_utilizados} / {$record->terminais_permitidos}")
                    ->color(fn(License $record) => $record->terminais_utilizados >= $record->terminais_permitidos ? 'warning' : 'gray'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
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
