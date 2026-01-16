<?php

namespace App\Filament\Resources\KbCategoryResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ArticlesRelationManager extends RelationManager
{
    protected static string $relationship = 'articles';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Ordem'),
                Tables\Columns\TextColumn::make('title')
                    ->label('Título'),
                Tables\Columns\IconColumn::make('is_public')
                    ->label('Público')
                    ->boolean(),
            ])
            ->defaultSort('kb_category_knowledge_base.sort_order', 'asc')
            ->reorderable('kb_category_knowledge_base.sort_order')
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make(),
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
