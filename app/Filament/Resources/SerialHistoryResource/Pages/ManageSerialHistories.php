<?php

namespace App\Filament\Resources\SerialHistoryResource\Pages;

use App\Filament\Resources\SerialHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSerialHistories extends ManageRecords
{
    protected static string $resource = SerialHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
