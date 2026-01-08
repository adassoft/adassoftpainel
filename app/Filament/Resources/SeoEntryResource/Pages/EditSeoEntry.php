<?php

namespace App\Filament\Resources\SeoEntryResource\Pages;

use App\Filament\Resources\SeoEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSeoEntry extends EditRecord
{
    protected static string $resource = SeoEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
