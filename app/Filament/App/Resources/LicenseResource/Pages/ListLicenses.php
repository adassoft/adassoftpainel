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

    public function desvincularTerminalAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('desvincularTerminal')
            ->requiresConfirmation()
            ->modalHeading('Desvincular Terminal')
            ->modalDescription('Tem certeza que deseja desvincular este terminal? Ele perderá o acesso ao software imediatamente.')
            ->modalSubmitActionLabel('Sim, desvincular')
            ->color('danger')
            ->action(function (array $arguments) {
                $terminalId = $arguments['terminal_id'] ?? null;
                $licenseId = $arguments['license_id'] ?? null;

                if (!$terminalId || !$licenseId) {
                    return;
                }

                try {
                    \Illuminate\Support\Facades\DB::table('terminais_software')
                        ->where('terminal_codigo', $terminalId)
                        ->where('licenca_id', $licenseId)
                        ->update(['ativo' => 0]);

                    \Filament\Notifications\Notification::make()
                        ->title('Terminal desvinculado')
                        ->success()
                        ->send();

                    $this->redirect(request()->header('Referer'));
                } catch (\Exception $e) {
                    \Filament\Notifications\Notification::make()
                        ->title('Erro ao desvincular')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
