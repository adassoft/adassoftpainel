<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SuggestionResource\Pages;
use App\Filament\Resources\SuggestionResource\RelationManagers;
use Illuminate\Support\Str;
use App\Models\Suggestion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SuggestionResource extends Resource
{
    protected static ?string $model = Suggestion::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalhes da Sugestão')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Select::make('software_id')
                            ->label('Software Relacionado')
                            ->relationship('software', 'nome_software')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('status')
                            ->label('Status Atual')
                            ->options([
                                'pending' => 'Pendente',
                                'voting' => 'Em Votação',
                                'planned' => 'Planejado',
                                'in_progress' => 'Em Desenvolvimento',
                                'completed' => 'Concluído',
                                'rejected' => 'Rejeitado',
                            ])
                            ->default('pending')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Metadados')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Sugerido por')
                            ->relationship('user', 'name')
                            ->disabled(),
                        Forms\Components\TextInput::make('votes_count')
                            ->label('Total de Votos')
                            ->disabled(),
                    ])->columns(2)->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Sugestão')
                    ->searchable()
                    ->description(fn($record) => Str::limit($record->description, 50)),
                Tables\Columns\TextColumn::make('votes_count')
                    ->label('Votos')
                    ->sortable()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('software.nome_software')
                    ->label('Software')
                    ->sortable()
                    ->placeholder('Geral'),
                Tables\Columns\SelectColumn::make('status')
                    ->options([
                        'pending' => 'Pendente',
                        'voting' => 'Em Votação', // Visible to public
                        'planned' => 'Planejado',
                        'in_progress' => 'Em Dev',
                        'completed' => 'Concluído',
                        'rejected' => 'Rejeitado',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->label('Data'),
            ])
            ->defaultSort('votes_count', 'desc')
            ->filters([
                //
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
            'index' => Pages\ListSuggestions::route('/'),
            'create' => Pages\CreateSuggestion::route('/create'),
            'edit' => Pages\EditSuggestion::route('/{record}/edit'),
        ];
    }
}
