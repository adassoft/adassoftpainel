<?php

namespace App\Filament\Resources\ResellerConfigResource\Pages;

use App\Filament\Resources\ResellerConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageResellerConfigs extends ManageRecords
{
    protected static string $resource = ResellerConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->slideOver()
                ->label('Nova Configuração'),
        ];
    }
}
