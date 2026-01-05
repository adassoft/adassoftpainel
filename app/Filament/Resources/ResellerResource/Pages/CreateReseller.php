<?php

namespace App\Filament\Resources\ResellerResource\Pages;

use App\Filament\Resources\ResellerResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateReseller extends CreateRecord
{
    protected static string $resource = ResellerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['acesso'] = 2; // Garante que é criado como Revenda
        return $data;
    }

    protected function afterCreate(): void
    {
        $user = $this->record;

        if (!empty($user->cnpj)) {
            $cleanCnpj = preg_replace('/\D/', '', $user->cnpj);

            // Verifica se empresa já existe
            $empresa = \App\Models\Company::where('cnpj', $cleanCnpj)->first();

            if (!$empresa) {
                // Cria nova empresa
                $empresa = new \App\Models\Company();
                $empresa->cnpj = $cleanCnpj;
                $empresa->razao = $user->nome ?? 'Nova Revenda';
                $empresa->email = $user->email;
                $empresa->status = 'Ativo';
                $empresa->data = now();
                $empresa->save();
            }

            // Vincula ao usuário recém criado
            $user->empresa_id = $empresa->codigo;
            $user->saveQuietly();
        }
    }
}
