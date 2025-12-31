<?php

namespace App\Filament\Reseller\Resources\CompanyResource\Pages;

use App\Filament\Reseller\Resources\CompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Ação de deletar removida conforme solicitado
        ];
    }

    protected function getSaveFormAction(): Actions\Action
    {
        return parent::getSaveFormAction()
            ->label('Salvar Alterações');
    }
}
