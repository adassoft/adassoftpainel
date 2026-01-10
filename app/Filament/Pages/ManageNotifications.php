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
                Section::make('Notificações para cobranças antes do vencimento')
                    ->schema([
                        $this->makeNotificationRow(
                            'new_invoice',
                            'Avisar criação de novas cobranças',
                            'Esta mensagem será enviada quando você gerar novas cobranças.'
                        ),

                        $this->makeNotificationRow(
                            'invoice_changed',
                            'Avisar alteração no valor ou data de vencimento',
                            'Esta mensagem será enviada quando você alterar a data de vencimento ou o valor das cobranças.'
                        ),

                        $this->makeDaysBeforeRow(
                            'days_before_due',
                            'Enviar cobranças %s dias antes do vencimento',
                            'Esta mensagem será enviada quando faltar X dias para o vencimento das cobranças.'
                        ),

                        $this->makeNotificationRow(
                            'due_date',
                            'Enviar cobranças pendentes no dia do vencimento',
                            'Esta mensagem será enviada na data de vencimento da cobrança caso o seu cliente ainda não a tenha pago.'
                        ),

                        $this->makeNotificationRow(
                            'digital_line',
                            'Enviar linha digitável do boleto caso o cliente não o tenha impresso',
                            'Esta mensagem será enviada na data de vencimento do boleto caso o seu cliente ainda não o tenha impresso.'
                        ),
                    ]),

                Section::make('Notificações para cobranças vencidas')
                    ->schema([
                        $this->makeNotificationRow(
                            'overdue',
                            'Avisar cobrança vencida',
                            'Esta mensagem será enviada quando uma cobrança estiver vencida.'
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
