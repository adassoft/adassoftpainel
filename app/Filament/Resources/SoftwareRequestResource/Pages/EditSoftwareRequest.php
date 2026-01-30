<?php

namespace App\Filament\Resources\SoftwareRequestResource\Pages;

use App\Filament\Resources\SoftwareRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSoftwareRequest extends EditRecord
{
    protected static string $resource = SoftwareRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
