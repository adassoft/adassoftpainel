<?php

namespace App\Filament\Resources\ResellerResource\Pages;

use App\Filament\Resources\ResellerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReseller extends EditRecord
{
    protected static string $resource = ResellerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        // Se o campo 'is_revenda_padrao' estiver presente nos dados do Livewire
        if (array_key_exists('is_revenda_padrao', $this->data)) {
            $user = $this->record;

            // Garante carregamento fresco
            $user->refresh();

            if ($user->empresa) {
                // Atualiza apenas a flag na empresa
                $user->empresa->update([
                    'revenda_padrao' => $this->data['is_revenda_padrao'] ? 1 : 0
                ]);
            }
        }
    }
}
