<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class DocsJava extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cup-online';

    protected static ?string $navigationGroup = 'Conteúdo & Suporte';

    protected static ?string $navigationLabel = 'SDK Java';

    protected static ?string $title = 'Documentação SDK Java';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.docs-java';
}
