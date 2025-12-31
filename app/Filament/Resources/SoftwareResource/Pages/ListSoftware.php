<?php

namespace App\Filament\Resources\SoftwareResource\Pages;

use App\Filament\Resources\SoftwareResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSoftware extends ListRecords
{
    protected static string $resource = SoftwareResource::class;

    protected static ?string $title = 'Cadastro de Softwares';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Cadastrar Novo Software')
                ->icon('heroicon-o-plus')
                ->color('success'),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Resources\SoftwareResource\Widgets\SoftwareStatusInfoWidget::class,
        ];
    }
}
