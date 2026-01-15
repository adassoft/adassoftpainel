<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class DocsLazarus extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-code-bracket-square';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationGroup = 'Conteúdo & Suporte';

    protected static ?string $navigationLabel = 'SDK Lazarus';

    protected static ?string $title = 'Documentação SDK Lazarus';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.docs-lazarus';
}
