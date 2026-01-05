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
                    if (!$user->cnpj)
                        return;

                    $cleanCnpj = preg_replace('/\D/', '', $user->cnpj);
                    $empresa = \App\Models\Company::where('cnpj', $cleanCnpj)->first();

                    if ($empresa) {
                        $action->fill([
                            'razao' => $empresa->razao,
                            'asaas_access_token' => $empresa->asaas_access_token,
                            'revenda_padrao' => (bool) $empresa->revenda_padrao,
                        ]);
                    }
                })
                ->form([
                    Forms\Components\TextInput::make('razao')
                        ->label('Razão Social')
                        ->disabled(), // Apenas leitura para confirmar que achou a empresa certa

                    Forms\Components\TextInput::make('asaas_access_token')
                        ->label('Token Asaas')
                        ->password()
                        ->revealable(),

                    Forms\Components\Toggle::make('revenda_padrao')
                        ->label('Revenda Padrão'),
                ])
                ->action(function (array $data, EditReseller $livewire) {
                    $user = $livewire->record;
                    $cleanCnpj = preg_replace('/\D/', '', $user->cnpj);

                    $empresa = \App\Models\Company::where('cnpj', $cleanCnpj)->first();

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
                            ->title('Erro: Empresa não encontrada para este CNPJ.')
                            ->body('Verifique se o cadastro da empresa existe.')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
