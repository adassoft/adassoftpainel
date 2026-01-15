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

    protected static ?string $navigationGroup = 'Conteúdo & Suporte';
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

                Forms\Components\TextInput::make('video_url')
                    ->label('Vídeo do YouTube (URL)')
                    ->prefixIcon('heroicon-o-video-camera')
                    ->prefix('URL')
                    ->placeholder('https://youtube.com/watch?v=...')
                    ->helperText('Cole o link do vídeo para exibir no topo do artigo.')
                    ->url()
                    ->columnSpanFull(),

                \Amidesfahani\FilamentTinyEditor\TinyEditor::make('content')
                    ->label('Conteúdo')
                    ->required()
                    ->columnSpanFull()
                    ->minHeight(400)
                    ->maxHeight(600)
                    ->showMenuBar()
                    ->hintAction(
                        Forms\Components\Actions\Action::make('aiImprove')
                            ->label('Melhorar com IA')
                            ->icon('heroicon-o-sparkles')
                            ->color('info')
                            ->requiresConfirmation()
                            ->modalHeading('Melhorar Conteúdo com IA')
                            ->modalDescription('A IA analisará o texto atual e sugerirá melhorias de gramática, clareza e formatação. O conteúdo atual será substituído. Deseja continuar?')
                            ->action(function (Forms\Get $get, Forms\Set $set) {
                                $content = $get('content');
                                if (empty($content)) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('O conteúdo está vazio.')
                                        ->warning()
                                        ->send();
                                    return;
                                }

                                \Filament\Notifications\Notification::make()
                                    ->title('A IA está trabalhando...')
                                    ->body('Aguarde enquanto geramos a melhoria.')
                                    ->info()
                                    ->send();

                                try {
                                    $service = new \App\Services\GeminiService();
                                    $prompt = "Atue como um editor técnico sênior. Melhore o texto abaixo para um artigo de Base de Conhecimento (Help Desk). 
                                    Objetivos:
                                    1. Corrigir erros gramaticais e ortográficos.
                                    2. Melhorar a clareza, coesão e tom (profissional e prestativo).
                                    3. Manter a formatação HTML existente (negritos, listas, etc) e melhorar se necessário.
                                    
                                    IMPORTANTE: Retorne APENAS o HTML final do conteúdo, sem blocos de código markdown (```html), sem explicações extras. Apenas o HTML cru para ser inserido no editor.
                                    
                                    Conteúdo Original:
                                    " . $content;

                                    $response = $service->generateContent($prompt);

                                    if ($response['success']) {
                                        $newContent = $response['reply'];
                                        // Remove markdown code blocks if AI puts them despite instructions
                                        $newContent = preg_replace('/^```html\s*/i', '', $newContent);
                                        $newContent = preg_replace('/^```\s*/i', '', $newContent);
                                        $newContent = preg_replace('/\s*```$/', '', $newContent);

                                        $set('content', $newContent);

                                        \Filament\Notifications\Notification::make()
                                            ->title('Conteúdo melhorado com sucesso!')
                                            ->success()
                                            ->send();
                                    } else {
                                        throw new \Exception($response['error'] ?? 'Erro desconhecido');
                                    }
                                } catch (\Exception $e) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Erro ao chamar a IA')
                                        ->body($e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            })
                    ),

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
