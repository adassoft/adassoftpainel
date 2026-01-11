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
        // Se o usuário não estiver ativo, aborta
        if ($this->user->status !== 'Ativo') {
            return;
        }

        // VERIFICAÇÃO DE CONVERSÃO (Para mensagens de TRIAL)
        if (in_array($this->stage, ['checkin_day1', 'tips_day3', 'closing_day6'])) {
            // Se já comprou (tem pedido pago), cancela envio de mensagens de trial
            $hasPaidOrder = \App\Models\Order::where('user_id', $this->user->id)
                ->whereIn('status', ['paid', 'confirmed'])
                ->exists();

            if ($hasPaidOrder) {
                Log::info("Onboarding message ({$this->stage}) skipped for User {$this->user->id} because they already converted.");
                return;
            }
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
        $firstName = explode(' ', $this->user->nome)[0];

        switch ($this->stage) {
            case 'welcome':
                $messageWa = "Olá *{$firstName}*! Seja muito bem-vindo(a) ao *{$appName}*! 🚀\nEstamos muito felizes em ter você conosco.\n\nQualquer dúvida que tiver durante seus testes, pode chamar aqui. Estamos à disposição para ajudar você a tirar o máximo proveito do sistema.\n\nAbraços,\nEquipe {$appName}";

                $subjectEmail = "Bem-vindo ao {$appName}!";
                $bodyEmail = "Olá {$firstName},\n\nSeja muito bem-vindo ao {$appName}!\n\nEstamos felizes por sua escolha. Nossos sistemas foram desenvolvidos para facilitar sua gestão.\n\nLembre-se: estamos à inteira disposição para qualquer dúvida. Responda este e-mail ou nos chame no WhatsApp.\n\nAtenciosamente,\nEquipe {$appName}";
                break;

            case 'checkin_day1': // Dia 1
                $messageWa = "Oi *{$firstName}*, tudo bem?\n\nPassando rapidinho para saber se conseguiu acessar o sistema e se precisa de alguma ajuda nesse início?\n\nQualquer dificuldade, é só falar! 😉";

                $subjectEmail = "Tudo certo com o {$appName}?";
                $bodyEmail = "Olá {$firstName},\n\nComo foi seu primeiro dia com o {$appName}?\n\nSe tiver alguma dificuldade ou dúvida, por favor, não hesite em nos contatar. Queremos garantir que sua experiência seja excelente.\n\nAtenciosamente,\nEquipe {$appName}";
                break;

            case 'tips_day3': // Dia 3
                $messageWa = "Olá *{$firstName}*! 👋\n\nSó para lembrar que o sistema tem vários recursos que podem facilitar seu dia a dia.\nJá explorou todas as abas?\n\nSe precisar de um treinamento rápido ou dica, estamos por aqui!";

                $subjectEmail = "Dicas para aproveitar o {$appName}";
                $bodyEmail = "Olá {$firstName},\n\nEsperamos que esteja gostando do sistema.\n\nVocê sabia que temos vídeos e tutoriais que podem ajudar? Se precisar de algo específico, é só responder este e-mail.\n\nAtenciosamente,\nEquipe {$appName}";
                break;

            case 'closing_day6': // Dia 6 (Véspera do fim, se 7 dias)
                $messageWa = "Oi *{$firstName}*!\n\nSeu período de teste gratuito do {$appName} está quase acabando. ⏳\n\nO que achou da experiência? Vamos garantir sua licença oficial para não perder o acesso?\n\nMe avise se tiver alguma dúvida sobre os planos!";

                $subjectEmail = "Seu teste do {$appName} está acabando";
                $bodyEmail = "Olá {$firstName},\n\nSeu período de avaliação termina em breve.\n\nPara continuar aproveitando todos os benefícios sem interrupção, confirme sua assinatura hoje mesmo.\n\nSe tiver dúvidas sobre valores ou formas de pagamento, estamos à disposição.\n\nAtenciosamente,\nEquipe {$appName}";
                break;

            case 'post_purchase_15d': // 15 dias após compra
                $messageWa = "Olá *{$firstName}*! Tudo bem? 😃\n\nPassaram-se 15 dias desde que você ativou sua licença.\nEstá tudo correndo bem? Precisando de algum ajuste ou suporte, conta com a gente!\n\nSucesso!";

                $subjectEmail = "Como estão as coisas com o {$appName}?";
                $bodyEmail = "Olá {$firstName},\n\nFaz 15 dias que oficializamos nossa parceria.\n\nGostaríamos de saber se está tudo funcionando perfeitamente e se você precisa de algum auxílio adicional.\n\nConte sempre conosco!\n\nAtenciosamente,\nEquipe {$appName}";
                break;

            case 'payment_received':
                $messageWa = "Olá *{$firstName}*! 🤑\n\nRecebemos a confirmação do seu pagamento! Muito obrigado.\n\nEm instantes sua licença será liberada/renovada. Aguarde só um pouquinho...";
                $subjectEmail = "Pagamento Confirmado - {$appName}";
                $bodyEmail = "Olá {$firstName},\n\nRecebemos a confirmação do seu pagamento. Obrigado pela confiança!\n\nSua licença está sendo processada e será liberada automaticamente em alguns instantes.\n\nAtenciosamente,\nEquipe {$appName}";
                break;

            case 'license_released':
                // Extrai dados da licença se passados, senão tenta buscar
                // O Job serializa models, então se passarmos license... mas o job aceita User.
                // Vou buscar a licença mais recente ativa do usuário.
                $license = \App\Models\License::where('empresa_codigo', $this->user->empresa_id ?? 0)
                    ->where('status', 'Ativo')
                    ->orderByDesc('data_expiracao')
                    ->first();

                $validade = $license ? $license->data_expiracao->format('d/m/Y') : 'recém liberada';

                $messageWa = "Tudo pronto, *{$firstName}*! ✅\n\nSua licença foi liberada com sucesso!\n\n📅 *Validade:* {$validade}\n\nAgora é só aproveitar. Qualquer dúvida, estamos aqui!";

                $subjectEmail = "Sua Licença foi Liberada! - {$appName}";
                $bodyEmail = "Olá {$firstName},\n\nTudo pronto! Sua licença foi liberada com sucesso.\n\nValidade: {$validade}\n\nVocê já pode acessar o sistema normalmente.\n\nQualquer dúvida, entre em contato.\n\nAtenciosamente,\nEquipe {$appName}";
                break;
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
