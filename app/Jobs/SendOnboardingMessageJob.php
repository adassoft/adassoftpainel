<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\WhatsappService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendOnboardingMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $stage;

    /**
     * Create a new job instance.
     *
     * @param User $user
     * @param string $stage 'welcome', 'checkin_day1', 'tips_day3', etc.
     */
    public function __construct(User $user, string $stage)
    {
        $this->user = $user;
        $this->stage = $stage;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsappService $whatsappService): void
    {
        // Se o usuário não estiver ativo, aborta?
        if ($this->user->status !== 'Ativo') {
            return;
        }

        $config = $whatsappService->loadConfig();
        $appName = config('app.name', 'Adassoft');
        $phone = null;
        if ($this->user->empresa) {
            $phone = $this->user->empresa->fone;
        }
        // Fallback: Tenta campo celular do user se existir (não padrão no modelo atual mas common)
        if (!$phone && isset($this->user->celular)) {
            $phone = $this->user->celular;
        }

        // --- Mensagens ---
        $messageWa = '';
        $subjectEmail = '';
        $bodyEmail = '';

        switch ($this->stage) {
            case 'welcome':
                $firstName = explode(' ', $this->user->nome)[0];
                $messageWa = "Olá *{$firstName}*! Seja muito bem-vindo(a) ao *{$appName}*! 🚀\nEstamos muito felizes em ter você conosco.\n\nQualquer dúvida que tiver durante seus testes, pode chamar aqui. Estamos à disposição para ajudar você a tirar o máximo proveito do sistema.\n\nAbraços,\nEquipe {$appName}";

                $subjectEmail = "Bem-vindo ao {$appName}!";
                $bodyEmail = "Olá {$firstName},\n\nSeja muito bem-vindo ao {$appName}!\n\nEstamos felizes por sua escolha. Nossos sistemas foram desenvolvidos para facilitar sua gestão.\n\nLembre-se: estamos à inteira disposição para qualquer dúvida. Responda este e-mail ou nos chame no WhatsApp.\n\nAtenciosamente,\nEquipe {$appName}";
                break;

            case 'checkin_day1':
                $firstName = explode(' ', $this->user->nome)[0];
                $messageWa = "Oi *{$firstName}*, tudo bem?\n\nPassando rapidinho para saber se conseguiu acessar o sistema e se precisa de alguma ajuda nesse início?\n\nQualquer dificuldade, é só falar! 😉";

                $subjectEmail = "Tudo certo com o {$appName}?";
                $bodyEmail = "Olá {$firstName},\n\nComo foi seu primeiro dia com o {$appName}?\n\nSe tiver alguma dificuldade ou dúvida, por favor, não hesite em nos contatar. Queremos garantir que sua experiência seja excelente.\n\nAtenciosamente,\nEquipe {$appName}";
                break;

            // Futuro: day3, etc.
        }

        // 1. Enviar WhatsApp
        if ($phone && !empty($messageWa)) {
            $result = $whatsappService->sendMessage($config, $phone, $messageWa);
            if (!$result['success']) {
                Log::warning("Falha ao enviar WhatsApp onboarding ({$this->stage}) para User {$this->user->id}: " . ($result['error'] ?? 'Erro desconhecido'));
            } else {
                Log::info("WhatsApp onboarding sent ({$this->stage}) to User {$this->user->id}");
            }
        }

        // 2. Enviar E-mail (Se email válido)
        if ($this->user->email && !empty($subjectEmail)) {
            try {
                Mail::raw($bodyEmail, function ($message) use ($subjectEmail) {
                    $message->to($this->user->email)
                        ->subject($subjectEmail);
                });
                Log::info("Email onboarding sent ({$this->stage}) to User {$this->user->id}");
            } catch (\Exception $e) {
                Log::error("Falha ao enviar Email onboarding ({$this->stage}) para User {$this->user->id}: " . $e->getMessage());
            }
        }
    }
}
