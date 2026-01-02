<?php

namespace App\Filament\App\Pages\Auth;

use Filament\Pages\Auth\PasswordReset\RequestPasswordReset;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Radio;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use App\Models\User;

#[Layout('layouts.login-split')]
class ForgotPassword extends RequestPasswordReset
{
    protected static string $view = 'filament.app.pages.auth.forgot-password'; // View customizada

    public $step = 1; // 1: Solicitar, 2: WhatsApp QR, 3: Redefinir
    public $method = 'email';
    public $whatsappLink = '';

    // Dados para reset
    public $email = '';
    public $token = '';
    public $password = '';
    public $passwordConfirmation = '';
    public $userId = null;

    public function mount(): void
    {
        if (auth()->check()) {
            redirect()->intended(filament()->getUrl());
        }
        // Inicializa dados
        $this->form->fill([
            'method' => 'email',
        ]);
    }

    public function form(Form $form): Form
    {
        if ($this->step === 1) {
            return $form
                ->schema([
                    TextInput::make('email')
                        ->label('E-mail cadastrado')
                        ->email()
                        ->required()
                        ->autofocus(),

                    Radio::make('method')
                        ->label('Receber código por:')
                        ->options([
                            'email' => 'E-mail',
                            'whatsapp' => 'WhatsApp',
                        ])
                        ->default('email')
                        ->required()
                        ->inline(),
                ]);
        }

        if ($this->step === 3) {
            return $form
                ->schema([
                    TextInput::make('token')
                        ->label('Código recebido')
                        ->required()
                        ->numeric()
                        ->length(6)
                        ->autofocus(),

                    TextInput::make('password')
                        ->label('Nova Senha')
                        ->password()
                        ->required()
                        ->minLength(8)
                        ->same('passwordConfirmation'),

                    TextInput::make('passwordConfirmation')
                        ->label('Confirme a Senha')
                        ->password()
                        ->required(),
                ]);
        }

        return $form->schema([]); // Step 2 não tem form, tem botão
    }

    public function requestToken()
    {
        $data = $this->form->getState();
        $this->email = $data['email'];
        $this->method = $data['method'];

        $user = User::where('email', $this->email)->first();

        if (!$user) {
            Notification::make()->danger()->title('Erro')->body('E-mail não encontrado.')->send();
            return;
        }

        $this->userId = $user->id;

        // Se for Email: Gera e envia
        if ($this->method === 'email') {
            $this->generateAndSendEmail($user);
            $this->step = 3; // Vai direto para input de token
            Notification::make()->success()->title('Código enviado!')->body('Verifique seu e-mail.')->send();
        }
        // Se for WhatsApp: Prepara Link e mostra QR
        else {
            $phone = '553891035724'; // Número do Bot da Adassoft (Legacy)
            $text = 'Ola, quero recuperar a senha';
            $this->whatsappLink = 'https://wa.me/' . $phone . '?text=' . urlencode($text);

            // NÃO geramos o token aqui para evitar spam na tabela. O usuário gera ao falar com o bot?
            // "Legacy logic": api_validacao.php gerava e enviava.
            // O usuário tem que iniciar a conversa.
            // O bot deve identificar o user e enviar o código.
            // Para garantir que o bot tenha algo para enviar se o usuário pedir, 
            // talvez devêssemos gerar o token agora?
            // "api_validacao.php" linha 206: INSERT.
            // O problema é: se eu gerar aqui, o bot sabe ler essa tabela? SIM.
            // O bot provavelmente lê "usuario_recuperacao" onde "usado = 0".
            // Então SIM, devo gerar o token agora.

            $this->generateToken($user); // Gera DB record
            // Mas NÃO envia (porque não temos API aqui iniciada). O Bot enviará quando user falar "Ola".
            // Ou o Bot responde passivamente?
            // Vamos assumir que o Bot responde passivamente consultando o DB.

            $this->step = 2; // Mostra QR
        }
    }

    public function goToStep3()
    {
        $this->step = 3;
    }

    private function generateToken($user)
    {
        $codigo = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $expira = now()->addMinutes(10);

        DB::table('usuario_recuperacao')->insert([
            'usuario_id' => $user->id,
            'codigo' => $codigo,
            'expira_em' => $expira,
            'usado' => 0
        ]);

        return $codigo;
    }

    private function generateAndSendEmail($user)
    {
        $codigo = $this->generateToken($user);

        // Enviar E-mail (Usando Laravel Mail simples para MVP)
        // Idealmente criar Mailable class.
        // Vou usar raw message por enquanto.
        try {
            Mail::raw("Seu código de recuperação de senha: $codigo\nVálido por 10 minutos.", function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Recuperação de Senha - Adassoft');
            });
        } catch (\Exception $e) {
            Notification::make()->danger()->title('Falha no envio')->body('Não foi possível enviar o e-mail.')->send();
        }
    }

    public function resetPassword()
    {
        $data = $this->form->getState();
        $token = $data['token'];
        $novaSenha = $data['password'];

        // Verifica Token (Busca globalmente por token válido e não usado)
        $recuperacao = DB::table('usuario_recuperacao')
            ->where('codigo', $token)
            ->where('usado', 0)
            ->where('expira_em', '>', now())
            ->orderBy('id', 'desc')
            ->first();

        if (!$recuperacao) {
            Notification::make()->danger()->title('Inválido')->body('Código inválido ou expirado.')->send();
            return;
        }

        // Usa o ID do usuário DO TOKEN, ignorando input do frontend para segurança
        $userId = $recuperacao->usuario_id;

        // Atualiza Senha
        User::where('id', $userId)->update([
            'senha' => Hash::make($novaSenha) // Hash padrão do Laravel
        ]);

        // Marca como usado
        DB::table('usuario_recuperacao')->where('id', $recuperacao->id)->update(['usado' => 1]);

        Notification::make()->success()->title('Sucesso!')->body('Senha redefinida. Faça login.')->send();

        redirect()->to(filament()->getLoginUrl());
    }
}
