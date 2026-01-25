<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create_campaign')
                ->label('Nova Campanha de Mensagens')
                ->icon('heroicon-o-megaphone')
                ->url(fn() => \App\Filament\Resources\MessageCampaignResource::getUrl('create')),
        ];
    }
}
