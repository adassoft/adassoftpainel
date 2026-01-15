<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class DocsFiremonkey extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationGroup = 'Conteúdo & Suporte';

    protected static ?string $navigationLabel = 'SDK FireMonkey';

    protected static ?string $title = 'Documentação SDK FireMonkey (FMX)';

    protected static string $view = 'filament.pages.docs-firemonkey';
}
