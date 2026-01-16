<?php

namespace App\Filament\Components;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class SeoForm
{
    public static function make(): Group
    {
        return Group::make()
            ->relationship('seo')
            ->schema([
                Section::make('Otimização para Motores de Busca (SEO)')
                    ->description('Personalize como esta página aparece no Google e nas redes sociais.')
                    ->icon('heroicon-o-magnifying-glass')
                    ->collapsible()
                    ->collapsed() // Começa fechado para não poluir
                    ->schema([
                        Placeholder::make('preview')
                            ->hiddenLabel()
                            ->content(fn(\Filament\Forms\Components\Component $component) => view('filament.components.seo-preview', [
                                'titleStatePath' => $component->getContainer()->getStatePath() . '.title',
                                'descriptionStatePath' => $component->getContainer()->getStatePath() . '.description',
                            ]))
                            ->columnSpanFull(),

                        Placeholder::make('analysis')
                            ->hiddenLabel()
                            ->content(function (\Filament\Forms\Components\Component $component) {
                                $data = $component->getContainer()->getState();
                                $keyword = $data['focus_keyword'] ?? null;
                                $title = $data['title'] ?? null;
                                $description = $data['description'] ?? null;

                                // Tenta obter o conteúdo principal do formulário (hack para acessar dados do pai)
                                $livewire = $component->getLivewire();
                                $mainData = $livewire->data ?? [];
                                $content = $mainData['content'] ?? null;

                                $analysis = [];

                                if ($keyword) {
                                    // Title Check
                                    if ($title && Str::contains(Str::lower($title), Str::lower($keyword))) {
                                        $analysis[] = ['status' => 'success', 'message' => 'A palavra-chave foco aparece no título SEO.'];
                                    } else {
                                        $analysis[] = ['status' => 'error', 'message' => 'A palavra-chave foco não aparece no título SEO.'];
                                    }

                                    // Title Length
                                    $titleLen = Str::length($title);
                                    if ($titleLen >= 30 && $titleLen <= 60) {
                                        $analysis[] = ['status' => 'success', 'message' => 'O título tem um comprimento ideal.'];
                                    } elseif ($titleLen < 30) {
                                        $analysis[] = ['status' => 'warning', 'message' => 'O título é curto. Tente escrever mais de 30 caracteres.'];
                                    } else {
                                        $analysis[] = ['status' => 'warning', 'message' => 'O título é longo. O Google pode cortá-lo (ideal até 60).'];
                                    }

                                    // Description Check
                                    if ($description && Str::contains(Str::lower($description), Str::lower($keyword))) {
                                        $analysis[] = ['status' => 'success', 'message' => 'A palavra-chave foco aparece na meta descrição.'];
                                    } else {
                                        $analysis[] = ['status' => 'error', 'message' => 'A palavra-chave foco não aparece na meta descrição.'];
                                    }

                                    // Description Length
                                    $descLen = Str::length($description);
                                    if ($descLen >= 100 && $descLen <= 160) {
                                        $analysis[] = ['status' => 'success', 'message' => 'A meta descrição tem um comprimento ideal.'];
                                    } elseif ($descLen < 100) {
                                        $analysis[] = ['status' => 'warning', 'message' => 'A meta descrição é curta. Tente escrever mais de 100 caracteres.'];
                                    } else {
                                        $analysis[] = ['status' => 'warning', 'message' => 'A meta descrição é longa. O Google pode cortá-la (ideal até 160).'];
                                    }

                                    // Content Analysis
                                    if ($content) {
                                        $cleanContent = strip_tags($content);
                                        $keywordCount = substr_count(Str::lower($cleanContent), Str::lower($keyword));

                                        if ($keywordCount > 0) {
                                            $analysis[] = ['status' => 'success', 'message' => "A palavra-chave aparece {$keywordCount} vezes no conteúdo."];
                                        } else {
                                            $analysis[] = ['status' => 'error', 'message' => 'A palavra-chave não foi encontrada no conteúdo do artigo.'];
                                        }
                                    } else {
                                        $analysis[] = ['status' => 'warning', 'message' => 'Adicione conteúdo ao artigo para análise de densidade.'];
                                    }
                                }

                                return view('filament.components.seo-analysis', [
                                    'analysis' => $analysis,
                                    'focus_keyword' => $keyword,
                                ]);
                            })
                            ->columnSpanFull(),

                        TextInput::make('focus_keyword')
                            ->label('Palavra-chave Foco')
                            ->helperText('A palavra principal que você quer ranquear. Usada para análise de conteúdo.')
                            ->placeholder('Ex: Gestão de Vendas')
                            ->live(debounce: 500)
                            ->hintAction(
                                Action::make('suggest_keyword')
                                    ->icon('heroicon-m-sparkles')
                                    ->label('Sugerir do Conteúdo')
                                    ->action(function ($set, $component) {
                                        $livewire = $component->getLivewire();
                                        $content = $livewire->data['content'] ?? '';
                                        if (!$content) {
                                            \Filament\Notifications\Notification::make()->title('Adicione conteúdo primeiro.')->warning()->send();
                                            return;
                                        }

                                        $text = strtolower(html_entity_decode(strip_tags($content)));
                                        // Simple removal of punctuation
                                        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
                                        $words = str_word_count($text, 1, 'àáâãçéêíóôõúü');
                                        // Expanded stop words list for Portuguese
                                        $stopWords = ['de', 'a', 'o', 'que', 'e', 'do', 'da', 'em', 'um', 'para', 'com', 'não', 'uma', 'os', 'no', 'se', 'na', 'por', 'mais', 'as', 'dos', 'como', 'mas', 'foi', 'ao', 'ele', 'das', 'tem', 'seu', 'sua', 'ou', 'ser', 'quando', 'muito', 'há', 'nos', 'já', 'está', 'eu', 'também', 'só', 'pelo', 'pela', 'até', 'isso', 'ela', 'entre', 'depois', 'sem', 'mesmo', 'aos', 'seus', 'quem', 'nas', 'me', 'esse', 'eles', 'você', 'essa', 'num', 'nem', 'suas', 'meu', 'às', 'minha', 'têm', 'numa', 'pelos', 'elas', 'qual', 'nós', 'lhe', 'deles', 'essas', 'esses', 'pelas', 'este', 'dele', 'tu', 'te', 'vocês', 'vos', 'lhes', 'meus', 'minhas', 'teu', 'tua', 'teus', 'tuas', 'nosso', 'nossa', 'nossos', 'nossas', 'dela', 'delas', 'esta', 'estes', 'estas', 'aquele', 'aquela', 'aqueles', 'aquelas', 'isto', 'aquilo', 'estou', 'está', 'estamos', 'estão', 'estive', 'esteve', 'estivemos', 'estiveram', 'estava', 'estávamos', 'estavam', 'estivera', 'estivéramos', 'esteja', 'estejamos', 'estejam', 'estivesse', 'estivéssemos', 'estivessem', 'estiver', 'estivermos', 'estiverem', 'hei', 'há', 'havemos', 'hão', 'houve', 'houvemos', 'houveram', 'houvera', 'houvéramos', 'haja', 'hajamos', 'hajam', 'houvesse', 'houvéssemos', 'houvessem', 'houver', 'houvermos', 'houverem', 'houverei', 'houverá', 'houveremos', 'houverão', 'houveria', 'houveríamos', 'houveriam', 'sou', 'somos', 'são', 'era', 'éramos', 'eram', 'fui', 'foi', 'fomos', 'foram', 'fora', 'fôramos', 'seja', 'sejamos', 'sejam', 'fosse', 'fôssemos', 'fossem', 'for', 'formos', 'forem', 'serei', 'será', 'seremos', 'serão', 'seria', 'seríamos', 'seriam', 'tenho', 'tem', 'temos', 'tém', 'tinha', 'tínhamos', 'tinham', 'tive', 'teve', 'tivemos', 'tiveram', 'tivera', 'tivéramos', 'tenha', 'tenhamos', 'tenham', 'tivesse', 'tivéssemos', 'tivessem', 'tiver', 'tivermos', 'tiverem', 'terei', 'terá', 'teremos', 'terão', 'teria', 'teríamos', 'teriam'];

                                        $words = array_diff($words, $stopWords);
                                        $wordCounts = array_count_values($words);
                                        arsort($wordCounts);

                                        $suggestion = array_key_first($wordCounts);
                                        if ($suggestion) {
                                            $set('focus_keyword', $suggestion);
                                            \Filament\Notifications\Notification::make()->title('Sugestão aplicada')->success()->send();
                                        } else {
                                            \Filament\Notifications\Notification::make()->title('Não foi possível sugerir uma palavra-chave.')->warning()->send();
                                        }
                                    })
                            ),

                        TextInput::make('title')
                            ->label('Título SEO')
                            ->placeholder('Título da página | Nome do Site')
                            ->maxLength(60) // Google corta ~60
                            ->live(debounce: 500)
                            ->afterStateUpdated(function ($state, $set) {
                                // Aqui poderíamos rodar validações em tempo real
                            }),

                        Textarea::make('description')
                            ->label('Meta Descrição')
                            ->rows(3)
                            ->maxLength(160) // Google corta ~160
                            ->placeholder('Um resumo atrativo do conteúdo...')
                            ->live(debounce: 500)
                            ->hintAction(
                                Action::make('generate_description')
                                    ->icon('heroicon-m-sparkles')
                                    ->label('Gerar do Conteúdo')
                                    ->action(function ($set, $component) {
                                        $livewire = $component->getLivewire();
                                        $content = $livewire->data['content'] ?? '';
                                        if (!$content) {
                                            \Filament\Notifications\Notification::make()->title('Adicione conteúdo primeiro.')->warning()->send();
                                            return;
                                        }

                                        $text = html_entity_decode(strip_tags($content));
                                        $description = Str::limit($text, 155, '');
                                        // Remove quebras de linha múltiplas
                                        $description = preg_replace('/\s+/', ' ', $description);

                                        $set('description', trim($description));
                                        \Filament\Notifications\Notification::make()->title('Descrição gerada com sucesso')->success()->send();
                                    })
                            ),


                        TextInput::make('canonical_url')
                            ->label('URL Canônica (Avançado)')
                            ->url()
                            ->helperText('Preencha apenas se quiser apontar que esta página é cópia de outra original.'),

                        TextInput::make('robots')
                            ->label('Robots (Indexação)')
                            ->default('index, follow')
                            ->helperText('Ex: noindex, nofollow (para esconder do Google)'),
                    ]),
            ]);
    }
}
