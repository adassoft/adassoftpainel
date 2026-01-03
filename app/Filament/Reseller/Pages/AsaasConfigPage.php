<?php

namespace App\Filament\Reseller\Pages;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class AsaasConfigPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Configurações';
    protected static ?string $navigationLabel = 'Configuração Asaas';
    protected static ?string $title = 'Integração Asaas (Recebimento Direto)';
    protected static string $view = 'filament.reseller.pages.asaas-config-page';

    public ?array $data = [];

    public function mount(): void
    {
        $companhia = Auth::user()->empresa;

        if ($companhia) {
            $this->form->fill([
                'api_key' => $companhia->asaas_access_token,
                'wallet_id' => $companhia->asaas_wallet_id,
                'asaas_mode' => $companhia->asaas_mode ?? 'production',
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Credenciais Asaas')
                    ->description('Configure sua chave de API para receber pagamentos diretamente.')
                    ->schema([
                        Select::make('asaas_mode')
                            ->label('Ambiente')
                            ->options([
                                'production' => 'Produção (Valendo Dinheiro)',
                                'sandbox' => 'Sandbox (Ambiente de Teste)',
                            ])
                            ->default('production')
                            ->required()
                            ->helperText('Selecione "Sandbox" apenas para testes com chaves da sandbox.asaas.com.'),

                        TextInput::make('api_key')
                            ->label('Chave de API (API Access Token)')
                            ->placeholder('$aact_...')
                            ->helperText('Obtenha esta chave no menu Integrações do seu painel Asaas.')
                            ->password() // Oculta por padrão
                            ->revealable()
                            ->required(),

                        TextInput::make('wallet_id')
                            ->label('Wallet ID (Opcional)')
                            ->placeholder('Ex: e6ab...')
                            ->helperText('Apenas se necessário para separar saldos (Subcontas).'),
                    ]),

                Section::make('Webhook Obrigatório')
                    ->schema([
                        Placeholder::make('webhook_url')
                            ->label('URL do Webhook')
                            ->content(new HtmlString('
                                <div class="bg-gray-100 dark:bg-gray-800 p-3 rounded text-sm font-mono break-all select-all border border-gray-300 dark:border-gray-600">
                                    ' . url('/api/webhooks/reseller/asaas') . '
                                </div>
                                <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    <ul class="list-disc pl-5 space-y-1">
                                        <li>Configure esta URL no painel do Asaas.</li>
                                        <li>Eventos Necessários: <strong>Cobranças</strong> (Pagamento Recebido, Vencido, Estornado).</li>
                                    </ul>
                                </div>
                            ')),
                    ])->collapsible(),

                Section::make('Como obter o Token?')
                    ->schema([
                        Placeholder::make('tutorial')
                            ->hiddenLabel()
                            ->content(new HtmlString('
                                <ol class="list-decimal pl-5 space-y-2 text-sm text-gray-600 dark:text-gray-300">
                                    <li>Acesse sua conta no <a href="https://www.asaas.com" target="_blank" class="text-blue-600 underline">www.asaas.com</a>.</li>
                                    <li>Vá em <strong>Configurações</strong> (engrenagem) -> <strong>Integrações</strong>.</li>
                                    <li>Clique em <strong>Gerar Chave de API</strong> (use a chave de Produção).</li>
                                    <li>Copie o código (começa com <code class="bg-gray-200 dark:bg-gray-700 px-1 rounded">$aact_</code>) e cole acima.</li>
                                </ol>
                            ')),
                    ])
                    ->collapsed(),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $companhia = Auth::user()->empresa;

        if (!$companhia) {
            Notification::make()
                ->title('Erro')
                ->body('Empresa não encontrada para este usuário.')
                ->danger()
                ->send();
            return;
        }

        $companhia->asaas_access_token = $data['api_key'];
        $companhia->asaas_wallet_id = $data['wallet_id'];
        $companhia->asaas_mode = $data['asaas_mode'];

        $companhia->save();

        Notification::make()
            ->title('Sucesso')
            ->body('Configurações do Asaas salvas com sucesso!')
            ->success()
            ->send();
    }
}
