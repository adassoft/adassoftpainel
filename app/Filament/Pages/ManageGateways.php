<?php

namespace App\Filament\Pages;

use App\Models\Gateway;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;

class ManageGateways extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Configurações';
    protected static ?string $title = 'Gateways de Pagamento';
    protected static ?string $slug = 'gateways';
    protected static string $view = 'filament.pages.manage-gateways';

    public function configureGatewayAction(): Action
    {
        return Action::make('configureGateway')
            ->label(fn($arguments) => isset($arguments['record_id']) ? 'Editar Gateway' : 'Cadastrar Gateway')
            ->modalHeading(fn($arguments) => 'Configurar ' . ucfirst($arguments['gateway'] ?? 'Gateway'))
            ->slideOver()
            ->modalWidth(MaxWidth::TwoExtraLarge)
            ->modalSubmitActionLabel('Salvar Alterações')
            ->modalCancelActionLabel('Cancelar')
            ->form([
                TextInput::make('gateway_name')
                    ->label('Nome do Gateway')
                    ->disabled()
                    ->required()
                    ->prefixIcon('heroicon-m-identification'),

                Select::make('active')
                    ->label('Status')
                    ->native(false)
                    ->selectablePlaceholder(false)
                    ->options([
                        1 => 'Habilitado',
                        0 => 'Desabilitado',
                    ])
                    ->required()
                    ->prefixIcon('heroicon-m-check-circle'),

                Select::make('producao')
                    ->label('Ambiente')
                    ->native(false)
                    ->selectablePlaceholder(false)
                    ->options([
                        'n' => 'Teste',
                        's' => 'Produção',
                    ])
                    ->required()
                    ->prefixIcon('heroicon-m-globe-alt'),

                // CAMPOS DO MERCADO PAGO
                \Filament\Forms\Components\Section::make('Configuração de Webhook (Mercado Pago)')
                    ->schema([
                        \Filament\Forms\Components\Placeholder::make('mp_webhook_url')
                            ->label('URL do Webhook')
                            ->content(fn() => url('/api/webhooks/mercadopago'))
                            ->helperText('Copie esta URL e configure no painel do Mercado Pago para receber notificações de pagamento.'),

                        TextInput::make('webhook_secret')
                            ->label('Assinatura Secreta (Webhook)')
                            ->password()
                            ->revealable()
                            ->helperText('Valor atual carregado; altere apenas se quiser trocar a assinatura secreta.')
                            ->prefixIcon('heroicon-m-lock-closed'),
                    ])
                    ->visible(fn($get) => in_array(strtolower($get('gateway_name')), ['mercado pago', 'mercadopago'])),

                TextInput::make('access_token')
                    ->label(fn($get) => strtolower($get('gateway_name')) === 'asaas' ? 'API Key (Asaas)' : 'Access Token (Mercado Pago)')
                    ->password()
                    ->revealable()
                    ->helperText('Valor atual carregado; altere se precisar trocar.')
                    ->visible(fn($get) => in_array(strtolower($get('gateway_name')), ['mercado pago', 'mercadopago', 'asaas']))
                    ->prefixIcon('heroicon-m-key'),

                TextInput::make('public_key')
                    ->label('Public Key (Mercado Pago)')
                    ->password()
                    ->revealable()
                    ->helperText('Valor atual carregado; altere se precisar trocar.')
                    ->visible(fn($get) => in_array(strtolower($get('gateway_name')), ['mercado pago', 'mercadopago']))
                    ->prefixIcon('heroicon-m-key'),

                TextInput::make('client_id')
                    ->label('Client ID (Mercado Pago)')
                    ->password()
                    ->revealable()
                    ->helperText('Valor atual carregado; altere se precisar trocar.')
                    ->visible(fn($get) => in_array(strtolower($get('gateway_name')), ['mercado pago', 'mercadopago']))
                    ->prefixIcon('heroicon-m-finger-print'),

                TextInput::make('client_secret')
                    ->label('Client Secret (Mercado Pago)')
                    ->password()
                    ->revealable()
                    ->helperText('Valor atual carregado; altere se precisar trocar.')
                    ->visible(fn($get) => in_array(strtolower($get('gateway_name')), ['mercado pago', 'mercadopago']))
                    ->prefixIcon('heroicon-m-key'),

                // CAMPOS DO ASAAS
                \Filament\Forms\Components\Section::make('Configuração de Webhook (Asaas)')
                    ->schema([
                        \Filament\Forms\Components\Placeholder::make('asaas_webhook_url')
                            ->label('URL do Webhook')
                            ->content(fn() => url('/api/webhooks/asaas'))
                            ->helperText('Copie esta URL e configure no painel do Asaas para receber notificações.'),
                    ])
                    ->visible(fn($get) => strtolower($get('gateway_name')) === 'asaas'),

                TextInput::make('wallet_id')
                    ->label('Wallet ID (Asaas)')
                    ->password()
                    ->revealable()
                    ->helperText('Valor atual carregado; altere se precisar trocar.')
                    ->visible(fn($get) => strtolower($get('gateway_name')) === 'asaas')
                    ->prefixIcon('heroicon-m-wallet'),

                TextInput::make('min_recharge')
                    ->label('Valor Mínimo para Recarga (R$)')
                    ->numeric()
                    ->prefix('R$')
                    ->placeholder('5,00')
                    ->helperText('Defina o valor mínimo permitido para recargas usando este gateway.')
                    ->visible(fn($get) => in_array(strtolower($get('gateway_name')), ['mercado pago', 'mercadopago', 'asaas']))
                    ->prefixIcon('heroicon-m-currency-dollar'),
            ])
            ->fillForm(function (array $arguments) {
                $gatewayName = $arguments['gateway'] === 'mercadopago' ? 'Mercado Pago' : 'Asaas';
                $record = Gateway::where('gateway_name', $gatewayName)
                    ->orWhere('gateway_name', strtolower($gatewayName))
                    ->orWhere('gateway_name', 'LIKE', $arguments['gateway'])
                    ->first();

                if ($record) {
                    return [
                        'gateway_name' => $record->gateway_name,
                        'active' => (int) $record->active,
                        'producao' => $record->producao,
                        'access_token' => $record->access_token,
                        'public_key' => $record->public_key,
                        'client_id' => $record->client_id,
                        'client_secret' => $record->client_secret,
                        'wallet_id' => $record->wallet_id,
                        'min_recharge' => $record->min_recharge,
                        'webhook_secret' => $record->webhook_secret ?? null,
                    ];
                }

                return [
                    'gateway_name' => $gatewayName,
                    'active' => 0,
                    'producao' => 'n',
                    'min_recharge' => 5.00,
                ];
            })
            ->action(function (array $data, array $arguments) {
                $gatewaySlug = $arguments['gateway'];
                $gatewayName = $data['gateway_name'];
                $targetName = $arguments['gateway'] === 'mercadopago' ? 'mercadopago' : 'asaas';

                $record = Gateway::where('gateway_name', 'LIKE', $targetName)->first();

                if ($record) {
                    $record->update($data);
                } else {
                    $gatewayName = $arguments['gateway'] === 'mercadopago' ? 'Mercado Pago' : 'Asaas';
                    Gateway::create(array_merge($data, ['gateway_name' => $gatewayName]));
                }

                Notification::make()
                    ->title('Gateway salvo com sucesso!')
                    ->success()
                    ->send();
            });
    }

    public function toggleStatusAction(): Action
    {
        return Action::make('toggleStatus')
            ->requiresConfirmation()
            ->action(function (array $arguments) {
                $record = Gateway::find($arguments['id']);
                if ($record) {
                    $record->update(['active' => !$record->active]);
                    Notification::make()
                        ->title('Status atualizado!')
                        ->success()
                        ->send();
                }
            });
    }

    protected function getViewData(): array
    {
        $dbGateways = Gateway::all()->keyBy(fn($item) => strtolower(str_replace(' ', '', $item->gateway_name)));

        $definitions = [
            'asaas' => [
                'name' => 'Asaas',
                'color' => 'info', // ou uma cor específica do asaas
            ],
            'mercadopago' => [
                'name' => 'Mercado Pago',
                'color' => 'primary', // azul
            ]
        ];

        $gateways = [];
        foreach ($definitions as $slug => $def) {
            $record = $dbGateways->get($slug);
            $gateways[$slug] = [
                'slug' => $slug,
                'name' => $def['name'],
                'color' => $def['color'],
                'record' => $record, // Pode ser null
            ];
        }

        return [
            'gateways' => $gateways,
        ];
    }
}
