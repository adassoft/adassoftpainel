<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SerialHistoryResource\Pages;
use App\Filament\Resources\SerialHistoryResource\RelationManagers;
use App\Models\SerialHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SerialHistoryResource extends Resource
{
    protected static ?string $model = SerialHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';
    protected static ?string $modelLabel = 'Histórico de Serial';
    protected static ?string $pluralModelLabel = 'Histórico de Seriais';
    protected static ?string $navigationGroup = 'Licenciamento';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.razao')
                    ->label('Empresa')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('software.nome_software')
                    ->label('Software')
                    ->sortable(),

                Tables\Columns\TextColumn::make('serial_gerado')
                    ->label('Serial')
                    ->fontFamily('mono')
                    ->copyable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('data_geracao')
                    ->label('Gerado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('validade_licenca')
                    ->label('Validade')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\IconColumn::make('ativo')
                    ->label('Ativo')
                    ->boolean(),
            ])
            ->defaultSort('data_geracao', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('software_id')
                    ->relationship('software', 'nome_software'),
                Tables\Filters\TernaryFilter::make('ativo'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // Read-only audit log, no bulk actions
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSerialHistories::route('/'),
        ];
    }
}
