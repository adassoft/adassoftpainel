<?php

namespace App\Filament\Resources\SeoEntryResource\Pages;

use App\Filament\Resources\SeoEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSeoEntries extends ListRecords
{
    protected static string $resource = SeoEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
