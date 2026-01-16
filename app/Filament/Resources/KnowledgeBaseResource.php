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

                Forms\Components\Tabs::make('ContentTabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Conteúdo')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\Grid::make(3)->schema([
                                    Forms\Components\Select::make('categories')
                                        ->relationship('categories', 'name')
                                        ->label('Categorias')
                                        ->multiple()
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

                                Forms\Components\Select::make('author_id')
                                    ->relationship('author', 'nome')
                                    ->label('Autor (Expertise)')
                                    ->searchable()
                                    ->preload()
                                    ->default(auth()->id())
                                    ->helperText('Selecione o especialista responsável para exibir a bio no artigo (E-E-A-T).')
                                    ->columnSpanFull(),

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

                                \AmidEsfahani\FilamentTinyEditor\TinyEditor::make('content')
                                    ->label('Conteúdo')
                                    ->required()
                                    ->columnSpanFull()
                                    ->minHeight(400)
                                    ->maxHeight(600)
                                    ->setCustomConfigs([
                                        'images_upload_url' => route('tinymce.upload'),
                                    ])

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
                                                    $keyword = $get('focus_keyword');

                                                    $seoInstructions = "";
                                                    if ($keyword) {
                                                        $seoInstructions = "4. OTIMIZAÇÃO SEO INDISPENSÁVEL:
                                                        - Palavra-chave Foco: '{$keyword}'
                                                        - A palavra-chave DEVE aparecer no primeiro parágrafo.
                                                        - A palavra-chave DEVE aparecer em pelo menos um subtítulo (H2 ou H3), se houver subtítulos.
                                                        - Espalhe a palavra-chave naturalmente pelo texto (densidade ~1%).
                                                        - Use negrito na primeira ocorrência da palavra-chave.";
                                                    } else {
                                                        $seoInstructions = "4. OTIMIZAÇÃO SEO:
                                                        - Use parágrafos curtos para facilitar a leitura (escaneabilidade).
                                                        - Use subtítulos (H2, H3) para quebrar o texto.
                                                        - Use listas (bullet points) onde apropriado.";
                                                    }

                                                    $prompt = "Atue como um editor técnico sênior e especialista em SEO. Melhore o texto abaixo para um artigo de Base de Conhecimento.
                                                    
                                                    Objetivos:
                                                    1. Corrigir erros gramaticais e ortográficos.
                                                    2. Melhorar a clareza, coesão e tom (profissional, direto e prestativo).
                                                    3. Manter TODA a formatação HTML existente (links, imagens, tabelas) e melhorar a estrutura (H2, H3) se necessário.
                                                    4. EXPANSÃO SEMÂNTICA (LSI): Identifique o tópico principal e enriqueça o texto usando sinônimos e termos correlatos (ex: se o tema for 'boleto', use também 'cobrança', 'fatura', 'título bancário', 'inadimplência'). Evite repetição excessiva da palavra-chave exata.
                                                    {$seoInstructions}
                                                    
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
                            ]),
                        Forms\Components\Tabs\Tab::make('Perguntas Frequentes (FAQ)')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                Forms\Components\Repeater::make('faq')
                                    ->label('Perguntas e Respostas')
                                    ->itemLabel(fn(array $state): ?string => $state['question'] ?? null)
                                    ->schema([
                                        Forms\Components\TextInput::make('question')
                                            ->label('Pergunta')
                                            ->required()
                                            ->columnSpanFull(),
                                        Forms\Components\Textarea::make('answer')
                                            ->label('Resposta')
                                            ->required()
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible()
                                    ->cloneable()

                                    ->addActionLabel('Adicionar Pergunta')
                                    ->hintAction(
                                        Forms\Components\Actions\Action::make('generateFaq')
                                            ->label('Gerar FAQ com IA')
                                            ->icon('heroicon-o-sparkles')
                                            ->color('info')
                                            ->requiresConfirmation()
                                            ->action(function (Forms\Get $get, Forms\Set $set) {
                                                $content = $get('content');
                                                if (!$content) {
                                                    \Filament\Notifications\Notification::make()->title('Adicione conteúdo primeiro.')->warning()->send();
                                                    return;
                                                }

                                                \Filament\Notifications\Notification::make()->title('Gerando perguntas...')->info()->send();

                                                try {
                                                    $service = new \App\Services\GeminiService();
                                                    $prompt = "Com base no texto abaixo, gere 3 a 5 perguntas frequentes (FAQ) relevantes com respostas curtas e diretas.
                                                        Retorne APENAS um JSON array neste formato:
                                                        [{\"question\": \"Pergunta?\", \"answer\": \"Resposta.\"}]
                                                        
                                                        Texto: " . strip_tags($content);

                                                    $response = $service->generateContent($prompt);
                                                    if ($response['success']) {
                                                        // Tenta extrair o JSON usando regex para ignorar textos fora do array
                                                        if (preg_match('/\[.*\]/s', $response['reply'], $matches)) {
                                                            $jsonString = $matches[0];
                                                            $faq = json_decode($jsonString, true);

                                                            if (is_array($faq)) {
                                                                $current = $get('faq') ?? [];
                                                                $set('faq', array_merge($current, $faq));
                                                                \Filament\Notifications\Notification::make()->title('FAQ Gerado!')->success()->send();
                                                            } else {
                                                                throw new \Exception('Falha ao decodificar JSON: ' . json_last_error_msg());
                                                            }
                                                        } else {
                                                            throw new \Exception('A IA não retornou um JSON array válido.');
                                                        }
                                                    } else {
                                                        throw new \Exception($response['error'] ?? 'Erro desconhecido na API.');
                                                    }
                                                } catch (\Exception $e) {
                                                    \Filament\Notifications\Notification::make()->title('Erro')->body($e->getMessage())->danger()->send();
                                                }
                                            })
                                    )
                            ]),

                        Forms\Components\Tabs\Tab::make('SEO')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                \App\Filament\Components\SeoForm::make(),
                            ]),
                    ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn($record) => \Illuminate\Support\Str::limit(html_entity_decode(strip_tags($record->content)), 50)),

                Tables\Columns\TextColumn::make('categories.name')
                    ->label('Categorias')
                    ->badge(),

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
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('categories')
                    ->relationship('categories', 'name')
                    ->label('Filtrar por Categoria'),
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
