<?php

namespace App\Filament\Resources\NewsResource\Pages;

use App\Filament\Resources\NewsResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateNews extends CreateRecord
{
    protected static string $resource = NewsResource::class;

    // Título da Página
    protected static ?string $title = 'Criar Nova Notícia';

    // Redirecionar para index após criar
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // Título da notificação de sucesso
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Notícia criada com sucesso!';
    }

    // Customizar Label do botão Criar
    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Publicar Notícia')
            ->icon('heroicon-o-paper-airplane');
    }

    // Customizar Label do botão Criar e Novo (ou desabilitar)
    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()
            ->label('Publicar e Criar Outra');
    }

    // Customizar Label do botão Cancelar
    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('Cancelar');
    }
}
