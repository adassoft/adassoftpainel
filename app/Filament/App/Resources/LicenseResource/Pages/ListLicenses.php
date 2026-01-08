<?php

namespace App\Filament\App\Resources\LicenseResource\Pages;

use App\Filament\App\Resources\LicenseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLicenses extends ListRecords
{
    protected static string $resource = LicenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\App\Resources\LicenseResource\Widgets\LicenseCssWidget::class,
        ];
    }

    public function desvincularTerminal($terminalId, $licenseId)
    {
        try {
            \Illuminate\Support\Facades\DB::table('terminais_software')
                ->where('terminal_codigo', $terminalId)
                ->where('licenca_id', $licenseId)
                ->update(['ativo' => 0]);

            \Filament\Notifications\Notification::make()
                ->title('Terminal desvinculado')
                ->success()
                ->send();

            // Força o recarregamento da página para atualizar o modal (já que modals puramente dinâmicos não reagem reativamente a changes externos facilmente sem fechar)
            // Idealmente usariamos um componente Livewire filho, mas refresh resolve rápido.
            $this->redirect(request()->header('Referer'));

        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Erro ao desvincular')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
