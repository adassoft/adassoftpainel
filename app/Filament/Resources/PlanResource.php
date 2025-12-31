<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Filament\Resources\PlanResource\RelationManagers;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $modelLabel = 'Plano de Venda';
    protected static ?string $pluralModelLabel = 'Planos de Venda';
    protected static ?string $navigationGroup = 'Comercial';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        // Coluna Principal (2/3 ou Full)
                        Forms\Components\Group::make()
                            ->columnSpan(fn($operation) => $operation === 'create' ? 3 : 2)
                            ->schema([
                                Forms\Components\Section::make(fn($operation) => $operation === 'create' ? 'Novo Plano' : 'Dados do Plano')
                                    ->icon(fn($operation) => $operation === 'create' ? 'heroicon-o-plus-circle' : 'heroicon-o-calendar')
                                    ->schema([
                                        Forms\Components\TextInput::make('nome_plano')
                                            ->label('Nome do Plano')
                                            ->required()
                                            ->placeholder(fn($operation) => $operation === 'create' ? 'Ex: Plano Mensal Premium' : 'Ex: Super Carnê = 01 mês')
                                            ->prefixIcon('heroicon-m-tag')
                                            ->prefixIconColor('primary')
                                            ->maxLength(255)
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('software_id')
                                            ->label('Software')
                                            ->relationship('software', 'nome_software')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->prefixIcon('heroicon-m-cube')
                                            ->prefixIconColor('primary')
                                            ->placeholder('Selecione um software...')
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('recorrencia')
                                            ->label('Recorrência (meses)')
                                            ->options([
                                                1 => '1 Mês',
                                                3 => '3 Meses',
                                                6 => '6 Meses',
                                                12 => '12 Meses (Anual)',
                                            ])
                                            ->required()
                                            ->prefixIcon('heroicon-m-arrow-path')
                                            ->prefixIconColor('primary')
                                            ->placeholder('Selecione...')
                                            ->default(null)
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('valor')
                                            ->label('Valor (R$)')
                                            ->prefixIcon('heroicon-m-currency-dollar')
                                            ->prefixIconColor('primary')
                                            ->numeric()
                                            ->required()
                                            ->placeholder('Ex: 50,00')
                                            ->columnSpanFull(),

                                        Forms\Components\Toggle::make('status')
                                            ->label('Plano Ativo')
                                            ->default(true)
                                            ->onIcon('heroicon-m-check')
                                            ->offIcon('heroicon-m-x-mark')
                                            ->onColor('success')
                                            ->offColor('danger')
                                            ->hidden(fn($operation) => $operation === 'create'),
                                    ]),
                            ]),

                        // Coluna Lateral (1/3)
                        Forms\Components\Group::make()
                            ->columnSpan(1)
                            ->hidden(fn($operation) => $operation === 'create')
                            ->schema([
                                Forms\Components\Section::make('Informações do Plano')
                                    ->schema([
                                        Forms\Components\Placeholder::make('id')
                                            ->label('ID do Plano:')
                                            ->content(fn($record) => '#' . $record?->id),

                                        Forms\Components\Placeholder::make('data_cadastro')
                                            ->label('Data de Cadastro:')
                                            ->content(fn($record) => $record?->data_cadastro?->format('d/m/Y H:i:s') ?? '-'),

                                        Forms\Components\Placeholder::make('dicas')
                                            ->label('Dicas para edição:')
                                            ->content(new \Illuminate\Support\HtmlString('
                                                <ul class="list-disc pl-4 text-sm text-gray-500 space-y-1">
                                                    <li>Nome deve ser descritivo e único</li>
                                                    <li>Software deve estar ativo</li>
                                                    <li>Recorrência em meses</li>
                                                    <li>Valor deve usar vírgula ou ponto</li>
                                                </ul>
                                            ')),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Lista de Planos')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->grow(false),
                Tables\Columns\TextColumn::make('nome_plano')
                    ->label('Nome do Plano')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('software.nome_software')
                    ->label('Software')
                    ->sortable()
                    ->description(
                        fn(Plan $record): string =>
                        ($record->software?->codigo ?? '-') . ' v' . ($record->software?->versao ?? '-')
                    )
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('recorrencia')
                    ->label('Recorrência')
                    ->suffix(' meses')
                    ->sortable()
                    ->alignCenter()
                    ->grow(false),
                Tables\Columns\TextColumn::make('valor')
                    ->label('Valor (R$)')
                    ->money('BRL')
                    ->sortable()
                    ->grow(false),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        '1', 'true', 'active' => 'success',
                        '0', 'false', 'inactive' => 'gray',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        '1', 'true', 'active' => 'Ativo',
                        '0', 'false', 'inactive' => 'Inativo',
                        default => $state,
                    })
                    ->grow(false),
                Tables\Columns\TextColumn::make('data_cadastro')
                    ->label('Data Cadastro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->striped()
            ->defaultSort('id', 'asc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->icon('heroicon-m-pencil-square')
                    ->button()
                    ->color('primary')
                    ->tooltip('Editar'),
                Tables\Actions\Action::make('toggle_status')
                    ->label('')
                    ->icon('heroicon-m-pause')
                    ->button()
                    ->color('warning')
                    ->action(fn(Plan $record) => $record->update(['status' => !$record->status]))
                    ->requiresConfirmation()
                    ->tooltip(fn(Plan $record) => $record->status ? 'Inativar' : 'Ativar'),
                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->icon('heroicon-m-trash')
                    ->button()
                    ->color('danger')
                    ->tooltip('Excluir'),
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
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
