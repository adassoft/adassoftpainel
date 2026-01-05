<?php

namespace App\Filament\Resources\ResellerResource\Pages;

use App\Filament\Resources\ResellerResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;

class EditReseller extends EditRecord
{
    protected static string $resource = ResellerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),

            Actions\Action::make('configurar_pagamento')
                ->label('Configurar Pagamento / Empresa')
                ->icon('heroicon-o-currency-dollar')
                ->color('success')
                ->mountUsing(function (Actions\Action $action, EditReseller $livewire) {
                    $user = $livewire->record;

                    // Tenta obter via relacionamento (ideal) ou fallback CNPJ (legado)
                    $empresa = $user->empresa;

                    if (!$empresa && $user->cnpj) {
                        // Fallback para caso o ID não tenha sido migrado ainda em runtime
                        $cleanCnpj = preg_replace('/\D/', '', $user->cnpj);
                        $empresa = \App\Models\Company::where('cnpj', $cleanCnpj)->first();

                        // Se achou, já salva o vínculo pra corrigir
                        if ($empresa) {
                            $user->empresa_id = $empresa->codigo;
                            $user->saveQuietly();
                        }
                    }

                    if ($empresa) {
                        $action->fillForm([
                            'razao' => $empresa->razao,
                            'asaas_access_token' => $empresa->asaas_access_token,
                            'revenda_padrao' => (bool) $empresa->revenda_padrao,
                        ]);
                    }
                })
                ->form([
                    Forms\Components\TextInput::make('razao')
                        ->label('Razão Social')
                        ->disabled(),

                    Forms\Components\TextInput::make('asaas_access_token')
                        ->label('Token Asaas')
                        ->password()
                        ->revealable(),

                    Forms\Components\Toggle::make('revenda_padrao')
                        ->label('Revenda Padrão'),
                ])
                ->action(function (array $data, EditReseller $livewire) {
                    $user = $livewire->record;
                    $empresa = $user->empresa; // Agora deve estar populado via update no mount ou já existente
        
                    // Fallback de segurança se o user não recarregou
                    if (!$empresa && $user->cnpj) {
                        $cleanCnpj = preg_replace('/\D/', '', $user->cnpj);
                        $empresa = \App\Models\Company::where('cnpj', $cleanCnpj)->first();

                        if ($empresa) {
                            $user->empresa_id = $empresa->codigo;
                            $user->saveQuietly();
                        }
                    }

                    if ($empresa) {
                        $empresa->update([
                            'asaas_access_token' => $data['asaas_access_token'],
                            'revenda_padrao' => $data['revenda_padrao'],
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Configurações salvas!')
                            ->success()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Erro: Nenhuma empresa vinculada.')
                            ->body('Verifique o CNPJ ou contate o suporte.')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
