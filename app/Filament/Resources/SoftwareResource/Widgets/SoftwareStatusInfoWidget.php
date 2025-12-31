<?php

namespace App\Filament\Resources\SoftwareResource\Widgets;

use Filament\Widgets\Widget;

class SoftwareStatusInfoWidget extends Widget
{
    protected static string $view = 'filament.resources.software-resource.widgets.software-status-info-widget';
    protected int|string|array $columnSpan = 'full';
}
