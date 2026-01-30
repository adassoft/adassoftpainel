<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SoftwareRequestResource\Pages;
use App\Filament\Resources\SoftwareRequestResource\RelationManagers;
use App\Models\SoftwareRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SoftwareRequestResource extends Resource
{
    protected static ?string $model = SoftwareRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-code-bracket';
    protected static ?string $navigationLabel = 'Projetos Sob Medida';
    protected static ?string $modelLabel = 'Solicitação';
    protected static ?string $navigationGroup = 'Comercial';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados do Cliente')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome')
                            ->required(),
                        Forms\Components\TextInput::make('email')
                            ->email(),
                        Forms\Components\TextInput::make('phone')
                            ->label('Telefone/WhatsApp'),
                        Forms\Components\TextInput::make('company')
                            ->label('Empresa'),
                    ])->columns(2),

                Forms\Components\Section::make('Detalhes do Projeto')
                    ->schema([
                        Forms\Components\TextInput::make('project_name')
                            ->label('Nome do Projeto'),
                        Forms\Components\TextInput::make('project_type')
                            ->label('Tipo de Projeto'),
                        Forms\Components\TextInput::make('budget_range')
                            ->label('Orçamento Estimado'),
                        Forms\Components\TextInput::make('deadline')
                            ->label('Prazo Desejado'),
                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->columnSpanFull()
                            ->rows(5),
                        Forms\Components\Textarea::make('features_list')
                            ->label('Funcionalidades Chave')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Gestão Interna')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'novo' => 'Novo Lead',
                                'em_analise' => 'Em Análise',
                                'contactado' => 'Já Contactado',
                                'fechado' => 'Fechado (Ganho)',
                                'perdido' => 'Perdido',
                            ])
                            ->default('novo')
                            ->required(),
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Anotações Internas')
                            ->columnSpanFull(),
                    ])->color('gray'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->label('Data')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Cliente')
                    ->searchable()
                    ->description(fn($record) => $record->company),
                Tables\Columns\TextColumn::make('project_type')
                    ->label('Tipo')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('budget_range')
                    ->label('Orçamento')
                    ->badge()
                    ->color('success'),
                Tables\Columns\SelectColumn::make('status')
                    ->options([
                        'novo' => 'Novo',
                        'em_analise' => 'Análise',
                        'contactado' => 'Contactado',
                        'fechado' => 'Fechado',
                        'perdido' => 'Perdido',
                    ])
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'novo' => 'Novo',
                        'em_analise' => 'Em Análise',
                        'contactado' => 'Já Contactado',
                        'fechado' => 'Fechado',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSoftwareRequests::route('/'),
            'create' => Pages\CreateSoftwareRequest::route('/create'),
            'edit' => Pages\EditSoftwareRequest::route('/{record}/edit'),
        ];
    }
}
