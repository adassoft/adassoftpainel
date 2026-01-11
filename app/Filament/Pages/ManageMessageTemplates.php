<?php

namespace App\Filament\Pages;

use App\Models\Configuration;
use App\Services\MessageTemplateService;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManageMessageTemplates extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-oval-left-ellipsis';
    protected static ?string $navigationLabel = 'Templates de Mensagem';
    protected static ?string $title = 'Editor de Templates';
    protected static ?string $navigationGroup = 'Configurações';
    protected static ?int $navigationSort = 5;
    protected static string $view = 'filament.pages.manage-message-templates';

    public ?array $data = [];

    public function mount(): void
    {
        $service = new MessageTemplateService();
        $this->form->fill($service->loadTemplates());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(12)
                    ->schema([
                        // Coluna Esquerda: Editores
                        Grid::make(1)
                            ->columnSpan(8)
                            ->schema([
                                Section::make('Cobrança Próxima ao Vencimento (Aviso Prévio)')
                                    ->description('Enviado X dias antes do vencimento (conforme config de notificações).')
                                    ->schema([
                                        Textarea::make('billing_due_soon_whatsapp')
                                            ->label('Mensagem WhatsApp')
                                            ->rows(3),
                                        Textarea::make('billing_due_soon_sms')
                                            ->label('Mensagem SMS (Recomendado ser curto)')
                                            ->rows(2),
                                    ]),

                                Section::make('Cobrança Vencida (Atraso)')
                                    ->description('Enviado diariamente após o vencimento.')
                                    ->schema([
                                        Textarea::make('billing_overdue_whatsapp')
                                            ->label('Mensagem WhatsApp')
                                            ->rows(3),
                                        Textarea::make('billing_overdue_sms')
                                            ->label('Mensagem SMS')
                                            ->rows(2),
                                    ]),
                            ]),

                        Section::make('Onboarding & Retenção (Novos Usuários)')
                            ->columnSpan(8)
                            ->collapsed()
                            ->schema([
                                // Welcome
                                Section::make('Boas-vindas (Imediato)')
                                    ->collapsed()
                                    ->schema([
                                        Textarea::make('onboarding_welcome_whatsapp')->label('WhatsApp')->rows(4),
                                        \Filament\Forms\Components\TextInput::make('onboarding_welcome_email_subject')->label('Assunto E-mail'),
                                        Textarea::make('onboarding_welcome_email_body')->label('Corpo E-mail')->rows(6),
                                    ]),

                                // Day 1
                                Section::make('Check-in Dia 1 (24h)')
                                    ->collapsed()
                                    ->schema([
                                        Textarea::make('onboarding_checkin_day1_whatsapp')->label('WhatsApp')->rows(4),
                                        \Filament\Forms\Components\TextInput::make('onboarding_checkin_day1_email_subject')->label('Assunto E-mail'),
                                        Textarea::make('onboarding_checkin_day1_email_body')->label('Corpo E-mail')->rows(6),
                                    ]),

                                // Day 3
                                Section::make('Dicas Dia 3')
                                    ->collapsed()
                                    ->schema([
                                        Textarea::make('onboarding_tips_day3_whatsapp')->label('WhatsApp')->rows(4),
                                        \Filament\Forms\Components\TextInput::make('onboarding_tips_day3_email_subject')->label('Assunto E-mail'),
                                        Textarea::make('onboarding_tips_day3_email_body')->label('Corpo E-mail')->rows(6),
                                    ]),

                                // Day 6
                                Section::make('Fechamento (Véspera do Fim)')
                                    ->collapsed()
                                    ->schema([
                                        Textarea::make('onboarding_closing_day6_whatsapp')->label('WhatsApp')->rows(4),
                                        \Filament\Forms\Components\TextInput::make('onboarding_closing_day6_email_subject')->label('Assunto E-mail'),
                                        Textarea::make('onboarding_closing_day6_email_body')->label('Corpo E-mail')->rows(6),
                                    ]),
                            ]),

                        Section::make('Confirmação & Pós-Venda')
                            ->columnSpan(8)
                            ->collapsed()
                            ->schema([
                                // Payment Received
                                Section::make('Pagamento Recebido')
                                    ->collapsed()
                                    ->schema([
                                        Textarea::make('onboarding_payment_received_whatsapp')->label('WhatsApp')->rows(3),
                                        \Filament\Forms\Components\TextInput::make('onboarding_payment_received_email_subject')->label('Assunto E-mail'),
                                        Textarea::make('onboarding_payment_received_email_body')->label('Corpo E-mail')->rows(5),
                                    ]),

                                // License Released
                                Section::make('Licença Liberada / Renovada')
                                    ->collapsed()
                                    ->schema([
                                        Textarea::make('onboarding_license_released_whatsapp')->label('WhatsApp')->rows(3),
                                        \Filament\Forms\Components\TextInput::make('onboarding_license_released_email_subject')->label('Assunto E-mail'),
                                        Textarea::make('onboarding_license_released_email_body')->label('Corpo E-mail')->rows(5),
                                    ]),

                                // Post Purchase 15d
                                Section::make('Check-in Pós-Venda (15 dias)')
                                    ->collapsed()
                                    ->schema([
                                        Textarea::make('onboarding_post_purchase_15d_whatsapp')->label('WhatsApp')->rows(3),
                                        \Filament\Forms\Components\TextInput::make('onboarding_post_purchase_15d_email_subject')->label('Assunto E-mail'),
                                        Textarea::make('onboarding_post_purchase_15d_email_body')->label('Corpo E-mail')->rows(5),
                                    ]),
                            ]),



                        // Coluna Direita: Dicas/Variables
                        Grid::make(1)
                            ->columnSpan(4)
                            ->schema([
                                Section::make('Variáveis Disponíveis')
                                    ->schema([
                                        ViewField::make('variables_help')
                                            ->view('filament.forms.components.template-variables-help'),
                                    ]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Configuration::updateOrCreate(
            ['chave' => 'message_templates'],
            ['valor' => json_encode($data)]
        );

        Notification::make()
            ->title('Templates atualizados com sucesso!')
            ->success()
            ->send();
    }
}
