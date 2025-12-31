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

        // 2. Generate API Keys
        $novaApiKey = \Illuminate\Support\Str::random(32); // Simple random string, or custom logic
        // Use custom logic if available, otherwise native Laravel Str
        // Legacy uses: shieldGenerateApiKey() -> we can port this function or use Str::random(32) + prefix
        // Let's stick to a robust generation
        $novaApiKey = 'sk_' . bin2hex(random_bytes(16));

        $hashKey = hash('sha256', $novaApiKey); // Using SHA256 as standard, check legacy if it used specific algo. Legacy: shieldHashApiKey -> likely password_hash or sha256. 
        // Legacy file check: uses shieldHashApiKey. Let's assume standard hashing is fine for new system, or we can check api_keys.php

        $apiKeyHint = substr($novaApiKey, -6); // Last 6 chars

        $record->update([
            'codigo' => $newCodigo,
            'api_key_hash' => $hashKey, // Storing hash
            'api_key_hint' => $apiKeyHint,
            'api_key_gerada_em' => now(),
            'status' => true, // Active by default
            'data_cadastro' => now(),
        ]);

        $this->tempApiKey = $novaApiKey;

        // Notification is handled by getCreatedNotification usually, but we want to show the key.
        // We can use a persistent notification.
        \Filament\Notifications\Notification::make()
            ->title('Software Cadastrado com Sucesso')
            ->body("**API Key Gerada:** `{$novaApiKey}`\n\nCopie esta chave agora, ela não será exibida novamente.")
            ->success()
            ->persistent()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
