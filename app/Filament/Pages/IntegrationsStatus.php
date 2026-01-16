<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class IntegrationsStatus extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-signal';
    protected static ?string $title = 'Status das Integrações';
    protected static ?string $navigationGroup = 'Integrações';
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.integrations-status';

    public function getViewData(): array
    {
        $feedUrl = route('feeds.google');

        $softwares = \App\Models\Software::where('status', true)->get()->map(function ($soft) {
            $warnings = [];

            if (empty($soft->descricao))
                $warnings[] = 'Sem descrição';
            if (empty($soft->imagem) && empty($soft->imagem_destaque))
                $warnings[] = 'Sem imagem';
            if ($soft->plans->where('status', true)->count() == 0)
                $warnings[] = 'Sem planos ativos (Preço 0)';
            if (empty($soft->google_product_category))
                $warnings[] = 'Sem Categoria Google (Usando padrão)';

            return [
                'name' => $soft->nome_software,
                'status' => empty($warnings) ? 'OK' : 'Atenção',
                'warnings' => $warnings,
                'link' => route('product.show', $soft->id),
            ];
        });

        return [
            'feedUrl' => $feedUrl,
            'softwares' => $softwares,
        ];
    }
}
