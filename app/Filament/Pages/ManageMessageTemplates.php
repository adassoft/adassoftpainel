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
