<?php

namespace App\Filament\Resources\DownloadResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('versao')
                    ->label('Versão')
                    ->required()
                    ->placeholder('v1.0.0')
                    ->maxLength(255),

                Forms\Components\FileUpload::make('arquivo_path')
                    ->label('Arquivo da Versão')
                    ->disk('public')
                    ->directory('downloads/versions')
                    ->required()
                    ->preserveFilenames()
                    ->live()
                    ->afterStateUpdated(function (\Filament\Forms\Set $set, $state) {
                        if (empty($state))
                            return;
                        $file = is_array($state) ? reset($state) : $state;
                        // Auto-calc size
                        if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                            $bytes = $file->getSize();
                            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
                            for ($i = 0; $bytes > 1024; $i++)
                                $bytes /= 1024;
                            $humanSize = round($bytes, 2) . ' ' . ($units[$i] ?? 'B');
                            $set('tamanho', $humanSize);
                        }
                    }),

                Forms\Components\TextInput::make('tamanho')
                    ->label('Tamanho')
                    ->readOnly()
                    ->placeholder('Calculado automaticamente'),

                Forms\Components\Textarea::make('changelog')
                    ->label('Notas da Versão (Changelog)')
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('contador')
                    ->label('Downloads')
                    ->numeric()
                    ->default(0)
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('versao')
            ->columns([
                Tables\Columns\TextColumn::make('versao')
                    ->label('Versão')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('tamanho')
                    ->label('Tamanho'),

                Tables\Columns\TextColumn::make('data_lancamento')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('contador')
                    ->label('Downloads')
                    ->badge()
                    ->color('success'),
            ])
            ->defaultSort('data_lancamento', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
