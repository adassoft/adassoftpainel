<?php

namespace App\Filament\Pages;

use App\Models\Configuration;
use App\Services\WhatsappService;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action;

class ManageWhatsapp extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Notificações WhatsApp';

    protected static ?string $title = 'Notificações por WhatsApp';

    protected static ?string $navigationGroup = 'Configurações';

    protected static string $view = 'filament.pages.manage-whatsapp';

    public ?array $data = [];

    public function mount(): void
    {
        $service = new WhatsappService();
        $config = $service->loadConfig();

        $initialData = array_merge($config, [
            'teste_destino' => '11988887777',
            'teste_mensagem' => 'Teste de WhatsApp - Shield',
        ]);

        $this->form->fill($initialData);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(12)
                    ->schema([
                        Grid::make(1)
                            ->columnSpan(7)
                            ->schema([
                                Section::make('Configuração WhatsApp Cloud API')
                                    ->extraAttributes(['class' => 'whatsapp-section-main'])
                                    ->schema([
                                        Checkbox::make('enabled')
                                            ->label('Habilitar envio por WhatsApp'),

                                        TextInput::make('access_token')
                                            ->label('Access Token')
                                            ->required()
                                            ->helperText('Token permanente da Meta (WhatsApp Cloud API).'),

                                        TextInput::make('phone_number_id')
                                            ->label('Phone Number ID')
                                            ->required()
                                            ->helperText('ID do número no painel do WhatsApp Cloud.'),

                                        Textarea::make('message_template')
                                            ->label('Template padrão')
                                            ->rows(4)
                                            ->placeholder('Olá {{empresa}}, sua licença do {{software}} expira em {{dias_restantes}} dia(s), em {{data_expiracao}}.')
                                            ->helperText('Placeholders suportados: {{empresa}}, {{software}}, {{dias_restantes}}, {{data_expiracao}}, {{licenca_id}}, {{empresa_codigo}}, {{software_id}}.'),

                                        TextInput::make('automation_secret')
                                            ->label('Segredo para automações (n8n/Chatwoot)')
                                            ->placeholder('Opcional')
                                            ->helperText('Se preenchido, requisições automatizadas devem enviar automacao_secret com este valor.'),
                                    ]),

                            ]),


                        Grid::make(1)
                            ->columnSpan(5)
                            ->schema([
                                Section::make('Teste de envio')
                                    ->extraAttributes(['class' => 'whatsapp-section-test'])
                                    ->schema([
                                        TextInput::make('teste_destino')
                                            ->label('Número de destino (somente dígitos, com DDD)')
                                            ->placeholder('11988887777'),

                                        Textarea::make('teste_mensagem')
                                            ->label('Mensagem')
                                            ->rows(3),

                                        \Filament\Forms\Components\Actions::make([
                                            \Filament\Forms\Components\Actions\Action::make('testSend')
                                                ->label('Enviar teste')
                                                ->color('success')
                                                ->action(fn() => $this->testSend()),
                                        ]),

                                        ViewField::make('test_note')
                                            ->view('filament.forms.components.whatsapp-test-note'),
                                    ]),

                                Section::make('Dicas WhatsApp Cloud')
                                    ->extraAttributes(['class' => 'whatsapp-section-tips'])
                                    ->schema([
                                        ViewField::make('whatsapp_tips')
                                            ->view('filament.forms.components.whatsapp-tips'),
                                    ]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Remove test fields before saving
        $configToSave = collect($data)->except(['teste_destino', 'teste_mensagem'])->toArray();

        Configuration::updateOrCreate(
            ['chave' => 'whatsapp_config'],
            ['valor' => json_encode($configToSave, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)]
        );

        Notification::make()
            ->title('Configuração salva com sucesso!')
            ->success()
            ->send();
    }

    public function testSend(): void
    {
        $data = $this->form->getState();

        if (empty($data['teste_destino'])) {
            Notification::make()
                ->title('Informe o destino')
                ->body('O campo destino é obrigatório para teste.')
                ->warning()
                ->send();
            return;
        }

        $service = new WhatsappService();

        $result = $service->sendMessage(
            $data,
            $data['teste_destino'] ?? '',
            $data['teste_mensagem'] ?? ''
        );

        if ($result['success']) {
            Notification::make()
                ->title('Mensagem de teste enviada com sucesso!')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Falha ao enviar mensagem!')
                ->body($result['error'])
                ->danger()
                ->send();
        }
    }
}
