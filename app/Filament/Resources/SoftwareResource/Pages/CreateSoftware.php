<?php

namespace App\Filament\Resources\SoftwareResource\Pages;

use App\Filament\Resources\SoftwareResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSoftware extends CreateRecord
{
    protected static string $resource = SoftwareResource::class;

    // We want to persist the "Generated API Key" to show in the notification/alert
    public $tempApiKey = null;

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        // 1. Generate Definitive Code (SW-00XX)
        $newCodigo = 'SW-' . str_pad($record->id, 4, '0', STR_PAD_LEFT);

        $record->update([
            'codigo' => $newCodigo,
            'status' => true, // Active by default
            'data_cadastro' => now(),
        ]);

        // Recupera a chave gerada pelo Model Observer (centralizada em api_keys)
        $novaApiKey = session('generated_api_key');

        if ($novaApiKey) {
            \Filament\Notifications\Notification::make()
                ->title('Software Cadastrado com Sucesso')
                ->body("**Nova API Key Gerada:** `{$novaApiKey}`\n\nCopie esta chave agora, ela foi criada em **Configurações > API Keys** e não será exibida novamente.")
                ->success()
                ->persistent()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
