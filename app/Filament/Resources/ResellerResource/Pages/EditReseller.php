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
                    // Garante dados frescos do banco
                    $user->refresh();

                    $empresa = $user->empresa;

                    // Fallback para caso o ID não tenha sido migrado ainda
                    if (!$empresa && $user->cnpj) {
                        $cleanCnpj = preg_replace('/\D/', '', $user->cnpj);
                        $empresa = \App\Models\Company::where('cnpj', $cleanCnpj)->first();

                        if ($empresa) {
                            // Se achou, já salva o vínculo pra corrigir para a próxima vez
                            $user->empresa_id = $empresa->codigo;
                            $user->saveQuietly();
                        }
                    }

                    if ($empresa) {
                        $action->fillForm([
                            'razao' => $empresa->razao,
                            'asaas_access_token' => $empresa->asaas_access_token,
                            'asaas_wallet_id' => $empresa->asaas_wallet_id,
                            'asaas_mode' => $empresa->asaas_mode ?? 'homologacao',
                            'revenda_padrao' => (bool) $empresa->revenda_padrao,
                        ]);
                    }
                })
                ->form([
                    Forms\Components\TextInput::make('razao')
                        ->label('Razão Social')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('asaas_access_token')
                        ->label('Token Asaas')
                        ->password()
                        ->revealable(),

                    Forms\Components\TextInput::make('asaas_wallet_id')
                        ->label('Wallet ID')
                        ->helperText('ID da carteira Asaas (opcional)'),

                    Forms\Components\Select::make('asaas_mode')
                        ->label('Ambiente Asaas')
                        ->options([
                            'homologacao' => 'Sandbox (Testes)',
                            'producao' => 'Produção',
                        ])
                        ->default('homologacao')
                        ->required(),

                    Forms\Components\Toggle::make('revenda_padrao')
                        ->label('Revenda Padrão')
                        ->default(false),
                ])
                ->action(function (array $data, EditReseller $livewire) {
                    $user = $livewire->record;
                    $empresa = $user->empresa;

                    // Fallback + Criação
                    if (!$empresa && $user->cnpj) {
                        $cleanCnpj = preg_replace('/\D/', '', $user->cnpj);
                        $empresa = \App\Models\Company::where('cnpj', $cleanCnpj)->first();

                        if (!$empresa) {
                            $empresa = new \App\Models\Company();
                            $empresa->cnpj = $cleanCnpj;
                            $empresa->razao = $data['razao'];
                            $empresa->email = $user->email;
                            $empresa->status = 'Ativo';
                            $empresa->data = now();
                            $empresa->save();

                            $user->empresa_id = $empresa->codigo;
                            $user->saveQuietly();
                        }
                    }

                    if ($empresa) {
                        $empresa->update([
                            'razao' => $data['razao'],
                            'asaas_access_token' => $data['asaas_access_token'],
                            'asaas_wallet_id' => $data['asaas_wallet_id'],
                            'asaas_mode' => $data['asaas_mode'],
                            // Removemos o cast complexo, confiando que o Toggle retorna true/false
                            // Se o form vier null, usamos false
                            'revenda_padrao' => $data['revenda_padrao'] ? 1 : 0,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Configurações salvas!')
                            ->success()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Erro: Usuário sem CNPJ.')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
