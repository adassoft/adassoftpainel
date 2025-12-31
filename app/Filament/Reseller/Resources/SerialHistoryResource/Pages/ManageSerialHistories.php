<?php

namespace App\Filament\Reseller\Resources\SerialHistoryResource\Pages;

use App\Filament\Reseller\Resources\SerialHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSerialHistories extends ManageRecords
{
    protected static string $resource = SerialHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
