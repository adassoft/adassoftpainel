<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SeoEntryResource\Pages;
use App\Filament\Resources\SeoEntryResource\RelationManagers;
use App\Models\SeoEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SeoEntryResource extends Resource
{
    protected static ?string $model = SeoEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationLabel = 'Páginas Estáticas (SEO)';
    protected static ?string $modelLabel = 'Página';
    protected static ?string $pluralModelLabel = 'SEO de Páginas Estáticas';
    protected static ?string $navigationGroup = 'Configurações de SEO';

    public static function getEloquentQuery(): Builder
    {
        // Só mostra entradas que NÃO estão vinculadas a Models (ou seja, páginas "avulsas")
        return parent::getEloquentQuery()->whereNull('model_type');
    }

    public static function form(Form $form): Form
    {
        // Reutilizamos a estrutura visual do SeoForm, mas adaptamos pois aqui editamos O PRÓPRIO registro
        // O SeoForm::make() retorna um Group com ->relationship('seo'). Aqui não queremos relationship.
        // Vamos extrair o Schema do SeoForm, removendo o relationship wrapper se possível, 
        // ou recriar manualmente para garantir flexibilidade.

        // Pela simplicidade e controle, vou replicar os campos aqui, pois têm uma especificidade (Url Path).

        return $form
            ->schema([
                Forms\Components\Section::make('Identificação da Página')
                    ->description('Defina para qual URL estes metadados se aplicam.')
                    ->schema([
                        Forms\Components\TextInput::make('url_path')
                            ->label('Caminho da URL')
                            ->placeholder('/contato')
                            ->helperText("Ex: '/' para Home, '/sobre' para Sobre Nós.")
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->prefix(config('app.url')),
                    ]),

                Forms\Components\Section::make('Metadados de SEO')
                    ->schema([
                        Forms\Components\ViewField::make('preview')
                            ->view('filament.components.seo-preview')
                            ->viewData([
                                'titleStatePath' => 'title',
                                'descriptionStatePath' => 'description',
                            ])
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('title')
                            ->label('Título da Página')
                            ->required()
                            ->maxLength(60),

                        Forms\Components\Textarea::make('description')
                            ->label('Meta Descrição')
                            ->maxLength(160)
                            ->rows(3),

                        Forms\Components\TextInput::make('keywords')
                            ->label('Palavras-chave')
                            ->placeholder('software, download, windows...'),

                        Forms\Components\TextInput::make('og_image')
                            ->label('Imagem de Compartilhamento (URL)')
                            ->placeholder('https://...'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('url_path')
                    ->label('URL')
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => $record->title),

                Tables\Columns\TextColumn::make('title')
                    ->label('Título SEO')
                    ->limit(30)
                    ->searchable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSeoEntries::route('/'),
            'create' => Pages\CreateSeoEntry::route('/create'),
            'edit' => Pages\EditSeoEntry::route('/{record}/edit'),
        ];
    }
}
