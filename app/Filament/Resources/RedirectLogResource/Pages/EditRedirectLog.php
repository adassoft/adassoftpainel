<?php

namespace App\Filament\Resources\RedirectLogResource\Pages;

use App\Filament\Resources\RedirectLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRedirectLog extends EditRecord
{
    protected static string $resource = RedirectLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
