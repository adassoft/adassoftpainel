<?php

namespace App\Filament\Pages;

use App\Models\MercadoLibreConfig;
use Filament\Forms\Components\Actions\Action; // Correto para Forms
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Filament\Actions\Action as PageAction; // Alias para Header Actions

class MercadoLibreIntegration extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel = 'Mercado Livre';
    protected static ?string $title = 'Integração Mercado Livre';
    protected static ?string $slug = 'mercado-libre-integration';

    protected static string $view = 'filament.pages.mercado-libre-integration';

    public ?array $data = [];
    public ?MercadoLibreConfig $config = null;

    public function mount(): void
    {
        // Carrega config global (company_id null) ou da empresa atual se tiver contexto
        // Como é uma página solta no Resource, vamos assumir Config Global por enquanto
        $this->config = MercadoLibreConfig::firstOrCreate(
            ['company_id' => null], // Global
            ['is_active' => false]
        );

        $this->form->fill([
            'app_id' => $this->config->app_id,
            'secret_key' => $this->config->secret_key,
            // 'redirect_uri' => route('ml.callback'), // Apenas exibição
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Credenciais de Aplicativo')
                    ->description('Insira as credenciais do seu aplicativo Mercado Livre (Developers Panel).')
                    ->schema([
                        TextInput::make('app_id')
                            ->label('App ID (Client ID)')
                            ->required(),
                        TextInput::make('secret_key')
                            ->label('Client Secret')
                            ->password()
                            ->revealable()
                            ->required(),
                        TextInput::make('redirect_uri_display')
                            ->label('Redirect URI (Configure isso no painel do ML)')
                            ->placeholder(route('ml.callback'))
                            ->default(route('ml.callback'))
                            ->readOnly()
                            ->suffixAction(
                                Action::make('copy')
                                    ->icon('heroicon-m-clipboard-document-check')
                                    ->action(function () {}) // No server action needed
                                    ->extraAttributes([
                                        'x-on:click' => 'window.navigator.clipboard.writeText("' . route('ml.callback') . '"); $tooltip("Copiado!", { timeout: 1500 });',
                                    ])
                            )
                            ->helperText('Copie esta URL e cole nas configurações do seu aplicativo no Mercado Livre.'),
                    ])
                    ->columns(2),

                Section::make('Status da Conexão')
                    ->schema([
                        TextInput::make('status')
                            ->label('Status')
                            ->default($this->config?->is_active ? 'Conectado como: ' . $this->config->ml_user_id : 'Desconectado')
                            ->disabled()
                            ->readOnly(),
                    ])
            ])
            ->statePath('data');
    }

    // Ação para salvar configurações
    public function save(): void
    {
        $data = $this->form->getState();

        $this->config->update([
            'app_id' => $data['app_id'],
            'secret_key' => $data['secret_key'],
            // redirect_uri é fixo na nossa ponta
            'redirect_uri' => route('ml.callback'),
        ]);

        Notification::make()
            ->title('Configurações salvas')
            ->success()
            ->send();
    }

    // Header Action para conectar
    protected function getHeaderActions(): array
    {
        return [
            PageAction::make('connect')
                ->label('Conectar com Mercado Livre')
                ->url(route('ml.auth'))
                ->openUrlInNewTab(false) // Redireciona mesmo
                ->visible(fn() => !empty($this->config->app_id) && !empty($this->config->secret_key))
                ->color('primary'),

            PageAction::make('save_top')
                ->label('Salvar Configurações')
                ->action('save')
                ->color('gray'),
        ];
    }
}
