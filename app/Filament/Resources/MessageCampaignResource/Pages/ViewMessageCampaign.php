<?php

namespace App\Filament\Resources\MessageCampaignResource\Pages;

use App\Filament\Resources\MessageCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMessageCampaign extends ViewRecord
{
    protected static string $resource = MessageCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
