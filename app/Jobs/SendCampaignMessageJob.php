<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCampaignMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $campaign;
    protected $license;

    public function __construct(\App\Models\MessageCampaign $campaign, $license)
    {
        $this->campaign = $campaign;
        $this->license = $license;
    }

    public function handle(): void
    {
        if (!$this->license->company)
            return; // Segurança

        $company = $this->license->company;

        // 1. Preparar Variáveis
        $vars = [
            '{name}' => $company->razao ?? 'Cliente',
            '{first_name}' => explode(' ', $company->razao ?? 'Cliente')[0],
            '{company}' => $company->razao ?? 'Sua Empresa',
            '{software}' => $this->license->nome_software ?? 'Software',
        ];

        // Mensagem Base
        $messageBody = $this->campaign->message;
        $formattedMessage = str_replace(array_keys($vars), array_values($vars), $messageBody);

        // Extrair Contatos
        $phone = $company->fone;
        $email = $company->email;
        $channels = $this->campaign->channels ?? [];

        // --- WhatsApp ---
        if (in_array('whatsapp', $channels) && $phone) {
            try {
                $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
                // Adiciona DDI 55 se faltar e tiver tamanho
                if (strlen($cleanPhone) >= 10 && strlen($cleanPhone) < 12) {
                    $cleanPhone = '55' . $cleanPhone;
                }

                $waService = new \App\Services\WhatsappService();
                $waConfig = $waService->loadConfig();
                $waService->sendMessage($waConfig, $cleanPhone, $formattedMessage);
                // Service already logs outcome
            } catch (\Exception $e) {
                // Log failure only if service threw exception outside its try-catch
                $this->logMessage('whatsapp', $phone, $formattedMessage, 'failed', $e->getMessage());
            }
        }

        // --- SMS ---
        if (in_array('sms', $channels) && $phone) {
            try {
                $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
                if (strlen($cleanPhone) >= 10 && strlen($cleanPhone) < 12) {
                    $cleanPhone = '55' . $cleanPhone;
                }

                $smsService = new \App\Services\SmsService();
                $smsConfig = $smsService->loadConfig();
                $smsService->sendSms($smsConfig, $cleanPhone, $formattedMessage);
                // Service already logs outcome
            } catch (\Exception $e) {
                $this->logMessage('sms', $phone, $formattedMessage, 'failed', $e->getMessage());
            }
        }

        // --- Email ---
        if (in_array('email', $channels) && $email) {
            try {
                \Illuminate\Support\Facades\Mail::raw($formattedMessage, function ($msg) use ($email) {
                    $msg->to($email)
                        ->subject($this->campaign->title);
                });

                // Email doesn't log automatically unless event listener catches it.
                // We have LogEmailSent listener, so it might duplicate if we log here?
                // LogEmailSent logs the *event*.
                // Let's rely on listener if it exists, or safe log here?
                // To be consistent with "Campaign", let's trust the unified log system.
                // But wait, the unified listener logs generic emails.
                // It's safer to not log duplicate here if listener is active.
                // Assuming listener is active:
                // \App\Listeners\LogEmailSent IS active in AppServiceProvider.

            } catch (\Exception $e) {
                $this->logMessage('email', $email, $formattedMessage, 'failed', $e->getMessage());
            }
        }

        // Atualiza Contador
        // Use increment safely
        $this->campaign->increment('processed_count');

        // Checagem de Conclusão (apenas uma estimativa, pois jobs são assíncronos)
        // Se processed >= total, marca como completo.
        if ($this->campaign->processed_count >= $this->campaign->total_targets) {
            $this->campaign->update(['status' => 'completed']);
        }
    }

    private function logMessage($channel, $recipient, $body, $status, $error = null)
    {
        \App\Models\MessageLog::create([
            'channel' => $channel,
            'recipient' => $recipient,
            'subject' => $this->campaign->title,
            'body' => $body,
            'status' => $status,
            'error_message' => $error,
            'sent_at' => now(),
        ]);
    }
}
