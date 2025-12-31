<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TerminalResource\Pages;
use App\Filament\Resources\TerminalResource\RelationManagers;
use App\Models\Terminal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TerminalResource extends Resource
{
    protected static ?string $model = Terminal::class;

    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';
    protected static ?string $modelLabel = 'Terminal';
    protected static ?string $pluralModelLabel = 'Controle de Terminais';
    protected static ?string $navigationGroup = 'Clientes e Licenças';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('FK_EMPRESA')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('MAC')
                    ->required()
                    ->maxLength(35),
                Forms\Components\TextInput::make('NOME_COMPUTADOR')
                    ->required()
                    ->maxLength(50),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('CODIGO')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('FK_EMPRESA')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('MAC')
                    ->searchable(),
                Tables\Columns\TextColumn::make('NOME_COMPUTADOR')
                    ->searchable(),
            ])
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
            'index' => Pages\ListTerminals::route('/'),
            'create' => Pages\CreateTerminal::route('/create'),
            'edit' => Pages\EditTerminal::route('/{record}/edit'),
        ];
    }
}
