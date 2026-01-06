<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KnowledgeBaseResource\Pages;
use App\Filament\Resources\KnowledgeBaseResource\RelationManagers;
use App\Models\KnowledgeBase;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KnowledgeBaseResource extends Resource
{
    protected static ?string $model = KnowledgeBase::class;

    protected static ?string $navigationGroup = 'Suporte e Ajuda';
    protected static ?string $modelLabel = 'Artigos da Base';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\Select::make('category_id')
                        ->relationship('category', 'name')
                        ->label('Categoria')
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')->required()->afterStateUpdated(fn($set, $state) => $set('slug', \Illuminate\Support\Str::slug($state)))->live(onBlur: true),
                            Forms\Components\TextInput::make('slug')->required(),
                        ])
                        ->searchable()
                        ->preload(),

                    Forms\Components\TextInput::make('title')
                        ->label('Título do Artigo')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn(Forms\Set $set, ?string $state) => $set('slug', \Illuminate\Support\Str::slug($state)))
                        ->columnSpan(2),
                ]),

                Forms\Components\TextInput::make('slug')
                    ->label('URL Amigável (Slug)')
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\RichEditor::make('content')
                    ->label('Conteúdo')
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\TagsInput::make('tags')
                    ->label('Palavras-chave (Tags)')
                    ->separator(',')
                    ->columnSpanFull(),

                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\Toggle::make('is_public')
                        ->label('Público (Sem Login)')
                        ->helperText('Se ativo, qualquer visitante pode ler.')
                        ->default(true),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Publicado')
                        ->default(true),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn($record) => \Illuminate\Support\Str::limit(strip_tags($record->content), 50)),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoria')
                    ->badge()
                    ->color(fn($record) => $record->category?->color ?? 'gray')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_public')
                    ->label('Público')
                    ->boolean()
                    ->trueIcon('heroicon-o-globe-alt')
                    ->falseIcon('heroicon-o-lock-closed'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListKnowledgeBases::route('/'),
            'create' => Pages\CreateKnowledgeBase::route('/create'),
            'edit' => Pages\EditKnowledgeBase::route('/{record}/edit'),
        ];
    }
}
