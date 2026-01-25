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

class SendLicenseNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $licenseData;

    /**
     * Create a new job instance.
     *
     * @param User $user
     * @param array $licenseData Data like ['validity' => 'd/m/Y', 'software_name' => '...']
     */
    public function __construct(User $user, array $licenseData)
    {
        $this->user = $user;
        $this->licenseData = $licenseData;
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

        $config = $whatsappService->loadConfig();
        $phone = null;
        if ($this->user->empresa) {
            $phone = $this->user->empresa->fone;
        }
        if (!$phone && isset($this->user->celular)) {
            $phone = $this->user->celular;
        }

        // Dados para o template
        $extraData = $this->licenseData;

        // Garante validade
        if (!isset($extraData['validity'])) {
            $extraData['validity'] = 'N/A';
        }

        // Pega templates de licença
        $messageWa = $templateService->getFormattedMessage('onboarding_license_released_whatsapp', $this->user, $extraData);
        $subjectEmail = $templateService->getFormattedMessage('onboarding_license_released_email_subject', $this->user, $extraData);
        $bodyEmail = $templateService->getFormattedMessage('onboarding_license_released_email_body', $this->user, $extraData);

        // 1. Enviar WhatsApp
        if ($phone && !empty($messageWa)) {
            $result = $whatsappService->sendMessage($config, $phone, $messageWa);
            if (!$result['success']) {
                Log::warning("Falha ao enviar WhatsApp License Notification para User {$this->user->id}: " . ($result['error'] ?? 'Erro desconhecido'));
            } else {
                Log::info("WhatsApp License Notification sent to User {$this->user->id}");
            }
        }

        // 2. Enviar E-mail
        if ($this->user->email && !empty($subjectEmail)) {
            try {
                Mail::raw($bodyEmail, function ($message) use ($subjectEmail) {
                    $message->to($this->user->email)
                        ->subject($subjectEmail);
                });
                Log::info("Email License Notification sent to User {$this->user->id}");
            } catch (\Exception $e) {
                Log::error("Falha ao enviar Email License Notification para User {$this->user->id}: " . $e->getMessage());
            }
        }
    }
}
