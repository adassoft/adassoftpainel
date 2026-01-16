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
                        ViewField::make('preview')
                            ->view('filament.components.seo-preview')
                            ->columnSpanFull(),

                        TextInput::make('focus_keyword')
                            ->label('Palavra-chave Foco')
                            ->helperText('A palavra principal que você quer ranquear. Usada para análise de conteúdo.')
                            ->placeholder('Ex: Gestão de Vendas')
                            ->live(onBlur: true),

                        TextInput::make('title')
                            ->label('Título SEO')
                            ->placeholder('Título da página | Nome do Site')
                            ->maxLength(60) // Google corta ~60
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set) {
                                // Aqui poderíamos rodar validações em tempo real
                            }),

                        Textarea::make('description')
                            ->label('Meta Descrição')
                            ->rows(3)
                            ->maxLength(160) // Google corta ~160
                            ->placeholder('Um resumo atrativo do conteúdo...')
                            ->live(onBlur: true),

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
