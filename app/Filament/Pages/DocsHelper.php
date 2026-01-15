<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class DocsHelper extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Conteúdo & Suporte';

    protected static ?string $navigationLabel = 'Documentação SDK';

    protected static ?string $title = 'Central de Documentação SDK';

    protected static ?string $slug = 'docs';

    protected static string $view = 'filament.pages.docs-helper';
}
