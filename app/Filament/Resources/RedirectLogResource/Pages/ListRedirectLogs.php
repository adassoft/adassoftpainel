<?php

namespace App\Filament\Resources\RedirectLogResource\Pages;

use App\Filament\Resources\RedirectLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRedirectLogs extends ListRecords
{
    protected static string $resource = RedirectLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
