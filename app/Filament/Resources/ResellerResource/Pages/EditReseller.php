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
                ->modalWidth('lg') // Remove slideOver e fixa tamanho
                ->mountUsing(function (Actions\Action $action, EditReseller $livewire) {
                    $user = $livewire->record;
                    $user->refresh();

                    // DEBUG: Verifique storage/logs/laravel.log
                    \Illuminate\Support\Facades\Log::info("EditReseller Mount: User ID {$user->id}, Empresa ID: {$user->empresa_id}");

                    $empresa = null;
                    if ($user->empresa_id) {
                        $empresa = \App\Models\Company::find($user->empresa_id);
                    }

                    // Fallback CNPJ se não achou por ID
                    if (!$empresa && $user->cnpj) {
                        $cleanCnpj = preg_replace('/\D/', '', $user->cnpj);
                        $empresa = \App\Models\Company::where('cnpj', $cleanCnpj)->first();

                        if ($empresa) {
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
                    } else {
                        // Preenchimento automático para criação
                        $action->fillForm([
                            'razao' => $user->nome ?? $user->login,
                            'asaas_mode' => 'homologacao',
                            'revenda_padrao' => false,
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
                        ->helperText('Opcional'),

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
                    \Illuminate\Support\Facades\Log::info("EditReseller Action: User ID {$user->id}, Data: " . json_encode($data));

                    // Busca fresca para evitar erro de objeto "stale"
                    $empresa = null;
                    if ($user->empresa_id) {
                        $empresa = \App\Models\Company::find($user->empresa_id);
                    }

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
                        // Update explícito usando Query Builder para evitar Mutators estranhos
                        \App\Models\Company::where('codigo', $empresa->codigo)->update([
                            'razao' => $data['razao'],
                            'asaas_access_token' => $data['asaas_access_token'],
                            'asaas_wallet_id' => $data['asaas_wallet_id'],
                            'asaas_mode' => $data['asaas_mode'],
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
