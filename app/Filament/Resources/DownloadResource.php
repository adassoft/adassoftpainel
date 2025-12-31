<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DownloadResource\Pages;
use App\Filament\Resources\DownloadResource\RelationManagers;
use App\Models\Download;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DownloadResource extends Resource
{
    protected static ?string $model = Download::class;

    protected static ?string $navigationIcon = 'heroicon-o-cloud-arrow-down';
    protected static ?string $navigationLabel = 'Gerenciar Downloads';
    protected static ?string $navigationGroup = 'Downloads';
    protected static ?string $modelLabel = 'Download';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('titulo')
                    ->label('Título')
                    ->required()
                    ->placeholder('Ex: Manual de Instalação')
                    ->columnSpanFull()
                    ->prefixIcon('heroicon-m-document-text'),

                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\TextInput::make('categoria')
                            ->label('Categoria')
                            ->datalist([
                                'Drivers',
                                'Manuais',
                                'Utilitários',
                                'Instaladores',
                            ])
                            ->placeholder('Ex: Drivers')
                            ->prefixIcon('heroicon-m-tag'),

                        Forms\Components\TextInput::make('versao')
                            ->label('Versão')
                            ->placeholder('v1.0.0')
                            ->prefixIcon('heroicon-m-information-circle'),

                        Forms\Components\TextInput::make('tamanho')
                            ->label('Tamanho')
                            ->placeholder('Ex: 5 MB')
                            ->helperText('Deixe vazio para auto-calcular no upload (se suportado)')
                            ->prefixIcon('heroicon-m-scale'),
                    ]),

                Forms\Components\FileUpload::make('arquivo_path')
                    ->label('Arquivo')
                    ->disk('public')
                    ->directory('downloads')
                    ->required()
                    ->maxSize(512000) // 500MB
                    ->columnSpanFull()
                    ->preserveFilenames(),

                Forms\Components\Textarea::make('descricao')
                    ->label('Descrição')
                    ->rows(2)
                    ->columnSpanFull(),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Toggle::make('publico')
                            ->label('Público (Visível para todos)')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger'),

                        Forms\Components\TextInput::make('contador')
                            ->label('Contador de Downloads')
                            ->numeric()
                            ->default(0)
                            ->prefixIcon('heroicon-m-arrow-down-tray'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('titulo')
                    ->label('Documento/Arquivo')
                    ->description(fn(Download $record) => $record->categoria)
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('versao')
                    ->label('Versão')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tamanho')
                    ->label('Tam.'),

                Tables\Columns\TextColumn::make('data_atualizacao')
                    ->label('Data/Info')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->description(fn(Download $record) => 'Downloads: ' . $record->contador),

                Tables\Columns\IconColumn::make('publico')
                    ->label('Público')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-lock-closed')
                    ->trueColor('success')
                    ->falseColor('warning'),
            ])
            ->defaultSort('data_atualizacao', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->modalWidth('2xl'),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('download')
                    ->label('')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn(Download $record) => '/storage/' . $record->arquivo_path)
                    ->openUrlInNewTab(),
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
            'index' => Pages\ListDownloads::route('/'),
        ];
    }
}
