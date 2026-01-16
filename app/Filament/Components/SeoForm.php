<?php

namespace App\Filament\Components;

use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Illuminate\Support\HtmlString;

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

                                $analysis = [];

                                if ($keyword) {
                                    // Title Check
                                    if ($title && \Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower($title), \Illuminate\Support\Str::lower($keyword))) {
                                        $analysis[] = ['status' => 'success', 'message' => 'A palavra-chave foco aparece no título SEO.'];
                                    } else {
                                        $analysis[] = ['status' => 'error', 'message' => 'A palavra-chave foco não aparece no título SEO.'];
                                    }

                                    // Title Length
                                    $titleLen = \Illuminate\Support\Str::length($title);
                                    if ($titleLen >= 30 && $titleLen <= 60) {
                                        $analysis[] = ['status' => 'success', 'message' => 'O título tem um comprimento ideal.'];
                                    } elseif ($titleLen < 30) { // Relaxed checking a bit
                                        $analysis[] = ['status' => 'warning', 'message' => 'O título é curto. Tente escrever mais de 30 caracteres.'];
                                    } else {
                                        $analysis[] = ['status' => 'warning', 'message' => 'O título é longo. O Google pode cortá-lo (ideal até 60).'];
                                    }

                                    // Description Check
                                    if ($description && \Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower($description), \Illuminate\Support\Str::lower($keyword))) {
                                        $analysis[] = ['status' => 'success', 'message' => 'A palavra-chave foco aparece na meta descrição.'];
                                    } else {
                                        $analysis[] = ['status' => 'error', 'message' => 'A palavra-chave foco não aparece na meta descrição.'];
                                    }

                                    // Description Length
                                    $descLen = \Illuminate\Support\Str::length($description);
                                    if ($descLen >= 100 && $descLen <= 160) {
                                        $analysis[] = ['status' => 'success', 'message' => 'A meta descrição tem um comprimento ideal.'];
                                    } elseif ($descLen < 100) {
                                        $analysis[] = ['status' => 'warning', 'message' => 'A meta descrição é curta. Tente escrever mais de 100 caracteres.'];
                                    } else {
                                        $analysis[] = ['status' => 'warning', 'message' => 'A meta descrição é longa. O Google pode cortá-la (ideal até 160).'];
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
                            ->live(debounce: 500),

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
                            ->live(debounce: 500),


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
