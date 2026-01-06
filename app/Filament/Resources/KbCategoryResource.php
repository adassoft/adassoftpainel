<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KbCategoryResource\Pages;
use App\Filament\Resources\KbCategoryResource\RelationManagers;
use App\Models\KbCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KbCategoryResource extends Resource
{
    protected static ?string $model = KbCategory::class;

    protected static ?string $navigationGroup = 'Suporte & Ajuda';
    protected static ?string $modelLabel = 'Categoria da Base';
    protected static ?string $navigationIcon = 'heroicon-o-folder-open';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nome da Categoria')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn(Forms\Set $set, ?string $state) => $set('slug', \Illuminate\Support\Str::slug($state))),

                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),

                Forms\Components\TextInput::make('description')
                    ->label('Descrição')
                    ->columnSpanFull(),

                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\Select::make('icon')
                        ->label('Ícone')
                        ->searchable()
                        ->options([
                            'heroicon-o-book-open' => 'Livro / Manual',
                            'heroicon-o-rocket-launch' => 'Primeiros Passos / Start',
                            'heroicon-o-chat-bubble-left-right' => 'Dúvidas / Chat',
                            'heroicon-o-currency-dollar' => 'Financeiro / Pagamentos',
                            'heroicon-o-cog-6-tooth' => 'Configurações Técnicas',
                            'heroicon-o-user-group' => 'Gestão de Usuários',
                            'heroicon-o-shield-check' => 'Segurança e Acesso',
                            'heroicon-o-exclamation-triangle' => 'Solução de Problemas',
                            'heroicon-o-question-mark-circle' => 'Perguntas Frequentes (FAQ)',
                            'heroicon-o-shopping-cart' => 'Vendas e Comercial',
                            'heroicon-o-document-text' => 'Documentação Geral',
                            'heroicon-o-computer-desktop' => 'Instalação / Sistema',
                            'heroicon-o-cloud-arrow-down' => 'Downloads e Atualizações',
                        ])
                        ->default('heroicon-o-book-open'),

                    Forms\Components\Select::make('color')
                        ->label('Cor de Destaque')
                        ->options([
                            'primary' => 'Azul (Primary)',
                            'success' => 'Verde (Success)',
                            'warning' => 'Amarelo (Warning)',
                            'danger' => 'Vermelho (Danger)',
                            'info' => 'Azul Claro (Info)',
                            'secondary' => 'Cinza (Secondary)',
                        ])
                        ->default('primary'),

                    Forms\Components\TextInput::make('sort_order')
                        ->label('Ordem')
                        ->numeric()
                        ->default(0),
                ]),

                Forms\Components\Toggle::make('is_active')->label('Ativo')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\IconColumn::make('icon')->icon(fn($state) => $state),
                Tables\Columns\TextColumn::make('color')->badge(),
                Tables\Columns\TextColumn::make('articles_count')
                    ->counts('articles')
                    ->label('Artigos'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('sort_order', 'asc')
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
            'index' => Pages\ListKbCategories::route('/'),
            'create' => Pages\CreateKbCategory::route('/create'),
            'edit' => Pages\EditKbCategory::route('/{record}/edit'),
        ];
    }
}
