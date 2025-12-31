<?php

namespace App\Filament\Pages;

use App\Models\Configuration;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Bridge\Smtp\Transport\SmtpSslTransport;
use Symfony\Component\Mailer\Bridge\Smtp\Transport\SmtpTransport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

class ManageEmail extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'Notificações Email';

    protected static ?string $title = 'Notificações por Email';

    protected static ?string $navigationGroup = 'Configurações';

    protected static string $view = 'filament.pages.manage-email';

    public ?array $data = [];

    public function mount(): void
    {
        $config = Configuration::where('chave', 'email_config')->first();

        $initialData = [
            'port' => 587,
            'secure' => 'tls',
            'from_name' => 'Adassoft Notificações',
            'teste_assunto' => 'Teste de SMTP - Shield',
            'teste_mensagem' => 'Teste automático de SMTP.',
        ];

        if ($config) {
            $initialData = array_merge($initialData, json_decode($config->valor, true));
        }

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
                                Section::make('Configuração SMTP (somente administradores)')
                                    ->extraAttributes(['class' => 'email-section-main'])
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('host')
                                                    ->label('Host')
                                                    ->required(),

                                                TextInput::make('port')
                                                    ->label('Porta')
                                                    ->numeric()
                                                    ->required(),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('username')
                                                    ->label('Usuário')
                                                    ->required(),

                                                TextInput::make('password')
                                                    ->label('Senha (app password)')
                                                    ->password()
                                                    ->revealable()
                                                    ->required(),
                                            ]),

                                        Grid::make(3)
                                            ->schema([
                                                Select::make('secure')
                                                    ->label('Segurança')
                                                    ->options([
                                                        '' => 'Sem TLS/SSL',
                                                        'tls' => 'TLS (587)',
                                                        'ssl' => 'SSL (465)',
                                                    ]),

                                                TextInput::make('from_email')
                                                    ->label('From (email)')
                                                    ->email()
                                                    ->required(),

                                                TextInput::make('from_name')
                                                    ->label('From (nome)')
                                                    ->required(),
                                            ]),

                                        \Filament\Forms\Components\Actions::make([
                                            \Filament\Forms\Components\Actions\Action::make('save')
                                                ->label('Salvar configuração')
                                                ->color('primary')
                                                ->submit('save'),
                                        ]),

                                        ViewField::make('gmail_note')
                                            ->view('filament.forms.components.email-gmail-note'),
                                    ]),
                            ]),

                        Grid::make(1)
                            ->columnSpan(5)
                            ->schema([
                                Section::make('Teste de envio')
                                    ->extraAttributes(['class' => 'email-section-test'])
                                    ->schema([
                                        TextInput::make('teste_destino')
                                            ->label('Email de destino')
                                            ->placeholder('destinatario@exemplo.com')
                                            ->email(),

                                        TextInput::make('teste_assunto')
                                            ->label('Assunto'),

                                        Textarea::make('teste_mensagem')
                                            ->label('Mensagem')
                                            ->rows(4),

                                        \Filament\Forms\Components\Actions::make([
                                            \Filament\Forms\Components\Actions\Action::make('testSend')
                                                ->label('Enviar teste')
                                                ->color('success')
                                                ->action(fn() => $this->testSend()),
                                        ]),

                                        ViewField::make('test_note_email')
                                            ->view('filament.forms.components.email-test-note'),
                                    ]),

                                Section::make('Dicas Gmail')
                                    ->extraAttributes(['class' => 'email-section-tips'])
                                    ->schema([
                                        ViewField::make('email_tips')
                                            ->view('filament.forms.components.email-tips'),
                                    ]),

                                Section::make('Agendar no aaPanel')
                                    ->extraAttributes(['class' => 'email-section-cron'])
                                    ->schema([
                                        ViewField::make('email_cron')
                                            ->view('filament.forms.components.email-cron'),
                                    ]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $configToSave = collect($data)->except(['teste_destino', 'teste_assunto', 'teste_mensagem'])->toArray();

        Configuration::updateOrCreate(
            ['chave' => 'email_config'],
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
                ->title('Informe o email de destino')
                ->body('O campo "Email de destino" é obrigatório para realizar o teste.')
                ->warning()
                ->send();
            return;
        }

        try {
            $transport = (new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
                $data['host'],
                $data['port'],
                $data['secure'] === 'ssl' // Only use implicit SSL/TLS if 'ssl' is selected. 'tls' means STARTTLS (handled automatically).
            ))
                ->setUsername($data['username'])
                ->setPassword($data['password']);

            $mailer = new \Symfony\Component\Mailer\Mailer($transport);

            $email = (new \Symfony\Component\Mime\Email())
                ->from(new \Symfony\Component\Mime\Address($data['from_email'], $data['from_name']))
                ->to($data['teste_destino'])
                ->subject($data['teste_assunto'])
                ->text($data['teste_mensagem']);

            $mailer->send($email);

            Notification::make()
                ->title('Email de teste enviado com sucesso!')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Falha ao enviar email!')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
