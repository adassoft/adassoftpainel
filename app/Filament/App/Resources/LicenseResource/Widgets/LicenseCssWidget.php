<?php

namespace App\Filament\App\Resources\LicenseResource\Widgets;

use Filament\Widgets\Widget;

class LicenseCssWidget extends Widget
{
    protected static string $view = 'filament.pages.license-custom-css';

    // Ocupar todo o espaço para garantir que seja renderizado, mas o conteúdo é invisível visualmente (apenas style)
    protected int|string|array $columnSpan = 'full';
}
