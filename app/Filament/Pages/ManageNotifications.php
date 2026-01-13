<?php

namespace App\Filament\Pages;

use App\Models\Configuration;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

class ManageNotifications extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-bell';
    protected static ?string $navigationLabel = 'Central de Notificações';
    protected static ?string $title = 'Central de Notificações';
    protected static ?string $navigationGroup = 'Configurações';
    protected static ?int $navigationSort = 4;
    protected static string $view = 'filament.pages.manage-notifications';

    public ?array $data = [];

    public function mount(): void
    {
        $config = Configuration::where('chave', 'notification_settings')->value('valor');
        $this->form->fill($config ? json_decode($config, true) : []);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Notificações de Vencimento (Licenças e Mensalidades)')
                    ->schema([
                        $this->makeNotificationRow(
                            'new_invoice',
                            'Avisar criação de novas assinaturas/licenças',
                            'Esta mensagem será enviada quando uma nova licença ou assinatura for gerada.'
                        ),

                        $this->makeNotificationRow(
                            'invoice_changed',
                            'Avisar alteração de vencimento',
                            'Esta mensagem será enviada se você alterar a data de expiração de uma licença manualmente.'
                        ),

                        $this->makeDaysBeforeRow(
                            'days_before_due',
                            'Avisar vencimento %s dias antes',
                            'Alerta antecipado avisando que a licença irá expirar em X dias.'
                        ),

                        $this->makeNotificationRow(
                            'due_date',
                            'Avisar no dia do vencimento',
                            'Mensagem enviada no dia exato que a licença expira.'
                        ),

                        $this->makeNotificationRow(
                            'digital_line',
                            'Enviar código PIX/Boleto pendente',
                            'Caso haja pagamento pendente (Asaas), reenvia o código no dia do vencimento.'
                        ),
                    ]),

                Section::make('Notificações de Atraso (Pós-Vencimento)')
                    ->schema([
                        $this->makeNotificationRow(
                            'overdue',
                            'Avisar licença expirada / vencida',
                            'Mensagem enviada após a data de expiração da licença.'
                        ),
                    ])
            ])
            ->statePath('data');
    }

    protected function makeNotificationRow(string $key, string $title, string $description)
    {
        return Grid::make(12)
            ->schema([
                Grid::make(1)
                    ->columnSpan(6)
                    ->schema([
                        Placeholder::make("{$key}_label")
                            ->label($title)
                            ->content($description)
                            ->extraAttributes(['class' => 'font-bold']),
                    ]),

                Grid::make(1)
                    ->columnSpan(3)
                    ->schema([
                        CheckboxList::make("{$key}.me")
                            ->label('Para mim:')
                            ->options([
                                'email' => 'Email',
                                'sms' => 'SMS',
                                'whatsapp' => 'WhatsApp'
                            ])
                            ->columns(1),
                    ]),

                Grid::make(1)
                    ->columnSpan(3)
                    ->schema([
                        CheckboxList::make("{$key}.customer")
                            ->label('Para meu cliente:')
                            ->options([
                                'email' => 'Email',
                                'sms' => 'SMS',
                                'whatsapp' => 'WhatsApp'
                            ])
                            ->columns(1),
                    ]),
            ])
            ->extraAttributes(['class' => 'border-b py-4']);
    }

    protected function makeDaysBeforeRow(string $key, string $title, string $description)
    {
        return Grid::make(12)
            ->schema([
                Grid::make(1)
                    ->columnSpan(6)
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make("{$key}.days")
                                    ->label('Dias antes')
                                    ->options(array_combine(range(1, 30), range(1, 30)))
                                    ->default(10)
                                    ->selectablePlaceholder(false)
                                    ->columnSpan(1),

                                Placeholder::make("{$key}_text")
                                    ->label('')
                                    ->content('dias antes do vencimento')
                                    ->extraAttributes(['class' => 'mt-8']) // Alignment hack
                                    ->columnSpan(1),
                            ]),
                        Placeholder::make("{$key}_desc")
                            ->label('')
                            ->content($description)
                            ->extraAttributes(['class' => 'text-sm text-gray-500']),
                    ]),

                Grid::make(1)
                    ->columnSpan(3)
                    ->schema([
                        CheckboxList::make("{$key}.me")
                            ->label('Para mim:')
                            ->options([
                                'email' => 'Email',
                                'sms' => 'SMS',
                                'whatsapp' => 'WhatsApp'
                            ])
                            ->columns(1),
                    ]),

                Grid::make(1)
                    ->columnSpan(3)
                    ->schema([
                        CheckboxList::make("{$key}.customer")
                            ->label('Para meu cliente:')
                            ->options([
                                'email' => 'Email',
                                'sms' => 'SMS',
                                'whatsapp' => 'WhatsApp'
                            ])
                            ->columns(1),
                    ]),
            ])
            ->extraAttributes(['class' => 'border-b py-4']);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Configuration::updateOrCreate(
            ['chave' => 'notification_settings'],
            ['valor' => json_encode($data)]
        );

        Notification::make()
            ->title('Configurações de notificação salvas!')
            ->success()
            ->send();
    }
}
