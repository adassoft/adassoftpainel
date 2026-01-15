<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class DocsDelphi extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationGroup = 'Conteúdo & Suporte';

    protected static ?string $navigationLabel = 'SDK Delphi';

    protected static ?string $title = 'Documentação SDK Delphi';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.docs-delphi';
}
