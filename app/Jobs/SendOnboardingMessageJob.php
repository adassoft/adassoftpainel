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
    public $customData;

    /**
     * Create a new job instance.
     *
     * @param User $user
     * @param string $stage 'welcome', 'checkin_day1', 'tips_day3', etc.
     * @param array $customData Optional data to override constraints or enrich message
     */
    public function __construct(User $user, string $stage, array $customData = [])
    {
        $this->user = $user;
        $this->stage = $stage;
        $this->customData = $customData;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsappService $whatsappService, \App\Services\MessageTemplateService $templateService): void
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
        $extraData = $this->customData; // Start with custom data

        // Prepara dados extras se necessário (apenas se não vieram no customData)
        if ($this->stage === 'license_released' && !isset($extraData['validity'])) {
            $query = \App\Models\License::where('status', 'Ativo');

            if (isset($extraData['license_id'])) {
                $query->where('id', $extraData['license_id']);
            } else {
                $query->where('empresa_codigo', $this->user->empresa_id ?? 0)
                    ->orderByDesc('data_expiracao');
            }

            $license = $query->first();

            if ($license) {
                $extraData['validity'] = $license->data_expiracao->format('d/m/Y');
            } else {
                $extraData['validity'] = 'N/A';
            }
        }

        // Mapeamento Stage -> Template Keys
        $keyMap = [
            'welcome' => [
                'wa' => 'onboarding_welcome_whatsapp',
                'subj' => 'onboarding_welcome_email_subject',
                'body' => 'onboarding_welcome_email_body'
            ],
            'checkin_day1' => [
                'wa' => 'onboarding_checkin_day1_whatsapp',
                'subj' => 'onboarding_checkin_day1_email_subject',
                'body' => 'onboarding_checkin_day1_email_body'
            ],
            'tips_day3' => [
                'wa' => 'onboarding_tips_day3_whatsapp',
                'subj' => 'onboarding_tips_day3_email_subject',
                'body' => 'onboarding_tips_day3_email_body'
            ],
            'closing_day6' => [
                'wa' => 'onboarding_closing_day6_whatsapp',
                'subj' => 'onboarding_closing_day6_email_subject',
                'body' => 'onboarding_closing_day6_email_body'
            ],
            'payment_received' => [
                'wa' => 'onboarding_payment_received_whatsapp',
                'subj' => 'onboarding_payment_received_email_subject',
                'body' => 'onboarding_payment_received_email_body'
            ],
            'license_released' => [
                'wa' => 'onboarding_license_released_whatsapp',
                'subj' => 'onboarding_license_released_email_subject',
                'body' => 'onboarding_license_released_email_body'
            ],
            'post_purchase_15d' => [
                'wa' => 'onboarding_post_purchase_15d_whatsapp',
                'subj' => 'onboarding_post_purchase_15d_email_subject',
                'body' => 'onboarding_post_purchase_15d_email_body'
            ],
        ];

        if (array_key_exists($this->stage, $keyMap)) {
            $keys = $keyMap[$this->stage];
            $messageWa = $templateService->getFormattedMessage($keys['wa'], $this->user, $extraData);
            $subjectEmail = $templateService->getFormattedMessage($keys['subj'], $this->user, $extraData);
            $bodyEmail = $templateService->getFormattedMessage($keys['body'], $this->user, $extraData);
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
