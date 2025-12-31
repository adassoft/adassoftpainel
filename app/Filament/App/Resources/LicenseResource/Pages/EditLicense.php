<?php

namespace App\Filament\App\Resources\LicenseResource\Pages;

use App\Filament\App\Resources\LicenseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLicense extends EditRecord
{
    protected static string $resource = LicenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
