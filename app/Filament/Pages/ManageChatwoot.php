<?php

namespace App\Filament\Pages;

use App\Models\Configuration;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Actions\Action;

class ManageChatwoot extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Integração Chatwoot';
    protected static ?string $navigationGroup = 'Integrações';
    protected static ?int $navigationSort = 5;
    protected static ?string $title = 'Chatwoot Live Chat';
    protected static string $view = 'filament.pages.manage-chatwoot';

    public ?array $data = [];

    public function mount(): void
    {
        $config = Configuration::where('chave', 'chatwoot')->first();
        if ($config) {
            $this->form->fill(json_decode($config->valor, true));
        } else {
            $this->form->fill(['enabled' => false]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Widget de Chat')
                    ->description('Habilite o chat em tempo real para seus clientes.')
                    ->schema([
                        Toggle::make('enabled')
                            ->label('Habilitar Widget')
                            ->default(false),

                        TextInput::make('base_url')
                            ->label('Base URL')
                            ->placeholder('https://app.chatwoot.com')
                            ->default('https://app.chatwoot.com')
                            ->required()
                            ->url(),

                        TextInput::make('website_token')
                            ->label('Website Token')
                            ->password()
                            ->revealable()
                            ->helperText('Encontrado em Configurações > Caixas de Entrada > Website > Configuração.')
                            ->required(fn($get) => $get('enabled')),
                    ])->columns(1),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Configuration::updateOrCreate(
            ['chave' => 'chatwoot'],
            ['valor' => json_encode($data)]
        );

        Notification::make()
            ->success()
            ->title('Configurações salvas com sucesso')
            ->send();
    }
}
