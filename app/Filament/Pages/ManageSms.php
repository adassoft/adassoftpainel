<?php

namespace App\Filament\Pages;

use App\Models\Configuration;
use App\Services\SmsService;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Forms\Components\Actions\Action;
use Illuminate\Support\Str;

class ManageSms extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';
    protected static ?string $navigationLabel = 'Configuração SMS';
    protected static ?string $title = 'Integração SMS';
    protected static ?string $navigationGroup = 'Integrações';
    protected static ?int $navigationSort = 4;
    protected static string $view = 'filament.pages.manage-sms';

    public ?array $data = [];

    public function mount(): void
    {
        $service = new SmsService();
        $config = $service->loadConfig();

        $this->form->fill(array_merge($config, [
            'test_phone' => '',
            'test_message' => 'Teste Adassoft SMS'
        ]));
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(12)
                    ->schema([
                        // Coluna da Esquerda: Configuração
                        Grid::make(1)
                            ->columnSpan(7)
                            ->schema([
                                Section::make('Configuração do Gateway')
                                    ->schema([
                                        Checkbox::make('enabled')
                                            ->label('Habilitar envio de SMS'),

                                        TextInput::make('api_url')
                                            ->label('URL da API (Endpoint)')
                                            ->placeholder('https://api.sms-gateway.com/send')
                                            ->required()
                                            ->url(),

                                        TextInput::make('api_key')
                                            ->label('Token / Chave de API')
                                            ->password()
                                            ->revealable(),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('param_phone')
                                                    ->label('Nome do parâmetro: Telefone')
                                                    ->default('phone')
                                                    ->helperText('Ex: number, celular, phone'),

                                                TextInput::make('param_message')
                                                    ->label('Nome do parâmetro: Mensagem')
                                                    ->default('message')
                                                    ->helperText('Ex: msg, text, content'),
                                            ]),
                                    ])
                                    ->description('Configure aqui a integração com seu provedor de SMS via HTTP POST.'),
                            ]),

                        // Coluna da Direita: Teste
                        Grid::make(1)
                            ->columnSpan(5)
                            ->schema([
                                Section::make('Testar Envio')
                                    ->schema([
                                        TextInput::make('test_phone')
                                            ->label('Telefone de Destino')
                                            ->mask('(99) 99999-9999')
                                            ->placeholder('(11) 99999-9999'),

                                        TextInput::make('test_message')
                                            ->label('Mensagem'),

                                        \Filament\Forms\Components\Actions::make([
                                            Action::make('sendTest')
                                                ->label('Enviar SMS de Teste')
                                                ->icon('heroicon-m-paper-airplane')
                                                ->action(fn() => $this->sendTest())
                                        ])->fullWidth(), // Ajuste visual se necessário
                                    ]),

                                Section::make('Serviços Recomendados')
                                    ->schema([
                                        ViewField::make('tips')
                                            ->view('filament.forms.components.sms-tips'),
                                    ])
                                    ->collapsible(),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        // Remove campos de teste
        unset($data['test_phone']);
        unset($data['test_message']);

        Configuration::updateOrCreate(
            ['chave' => 'sms_config'],
            ['valor' => json_encode($data)]
        );

        Notification::make()
            ->title('Configurações de SMS salvas!')
            ->success()
            ->send();
    }

    public function sendTest(): void
    {
        $data = $this->form->getState();

        if (empty($data['test_phone'])) {
            Notification::make()->title('Informe um telefone para teste.')->warning()->send();
            return;
        }

        $service = new SmsService();
        // Usa os dados do formulário atual, mesmo sem salvar (para testar antes de salvar)
        $result = $service->sendSms($data, $data['test_phone'], $data['test_message']);

        if ($result['success']) {
            Notification::make()
                ->title('SMS enviado com sucesso!')
                ->body('Resposta da API: ' . Str::limit($result['response'] ?? '', 50))
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Falha no envio')
                ->body($result['error'] ?? 'Erro desconhecido')
                ->danger()
                ->send();
        }
    }
}
