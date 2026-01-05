<?php

namespace App\Filament\Pages;

use App\Models\Configuration;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Actions\Action;

class ManageAapanel extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-server';
    protected static ?string $navigationLabel = 'Integração aaPanel';
    protected static ?string $navigationGroup = 'Configurações';
    protected static ?string $title = 'aaPanel (Automação de Domínios)';
    protected static string $view = 'filament.pages.manage-aapanel';

    public ?array $data = [];

    public function mount(): void
    {
        $config = Configuration::where('chave', 'aapanel_config')->first();
        if ($config) {
            $this->form->fill(json_decode($config->valor, true));
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Configuração de Conexão')
                    ->description('Conecte seu painel aaPanel para criar subdomínios automaticamente.')
                    ->schema([
                        \Filament\Forms\Components\Toggle::make('ativo')
                            ->label('Ativar Integração Automática')
                            ->helperText('Se desativado, o gerenciamento de domínios deverá ser feito manualmente no servidor.')
                            ->default(false)
                            ->live(),

                        TextInput::make('url')
                            ->label('URL do Painel')
                            ->placeholder('http://SEU_IP:8888')
                            ->helperText('Informe a URL completa de acesso ao painel, incluindo a porta.')
                            ->required(fn(\Filament\Forms\Get $get) => $get('ativo'))
                            ->url()
                            ->visible(fn(\Filament\Forms\Get $get) => $get('ativo')),

                        TextInput::make('main_domain')
                            ->label('Domínio Principal')
                            ->placeholder('express.adassoft.com')
                            ->helperText('O site pai no aaPanel que receberá os Aliases.')
                            ->required(fn(\Filament\Forms\Get $get) => $get('ativo'))
                            ->visible(fn(\Filament\Forms\Get $get) => $get('ativo')),

                        TextInput::make('key')
                            ->label('API Key')
                            ->password()
                            ->revealable()
                            ->helperText('Obtenha em Settings > API no seu aaPanel.')
                            ->required(fn(\Filament\Forms\Get $get) => $get('ativo'))
                            ->visible(fn(\Filament\Forms\Get $get) => $get('ativo')),
                    ])->columns(1),
            ])
            ->statePath('data');
    }



    public function save(): void
    {
        $data = $this->form->getState();

        Configuration::updateOrCreate(
            ['chave' => 'aapanel_config'],
            ['valor' => json_encode($data)]
        );

        Notification::make()
            ->success()
            ->title('Configurações salvas com sucesso')
            ->send();
    }
}
