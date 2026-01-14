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
        $rawMessage = $this->campaign->message;

        // Versão HTML (Para Email)
        $htmlMessage = str_replace(array_keys($vars), array_values($vars), $rawMessage);

        // Versão Texto (Para Zap/SMS) - Converter HTML em Texto
        // Substitui quebras visuais por quebras reais
        $textOnly = str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $rawMessage);
        // Remove todo o restante de tags
        $textOnly = strip_tags($textOnly);
        // Decodifica &nbsp; e outros
        $textOnly = html_entity_decode($textOnly);
        // Aplica variáveis
        $textMessage = str_replace(array_keys($vars), array_values($vars), $textOnly);
        // Remove excesso de linhas em branco do strip_tags
        $textMessage = preg_replace("/\n\s*\n\s*\n/", "\n\n", $textMessage);
        $textMessage = trim($textMessage);

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
                $waService->sendMessage($waConfig, $cleanPhone, $textMessage);
                // Service logs outcome
            } catch (\Exception $e) {
                // Log failure only if service threw exception outside its try-catch
                $this->logMessage('whatsapp', $phone, $textMessage, 'failed', $e->getMessage());
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
                $smsService->sendSms($smsConfig, $cleanPhone, $textMessage);
                // Service logs outcome
            } catch (\Exception $e) {
                $this->logMessage('sms', $phone, $textMessage, 'failed', $e->getMessage());
            }
        }

        // --- Email ---
        if (in_array('email', $channels) && $email) {
            try {
                // Usa Mail::html para enviar conteúdo rico
                \Illuminate\Support\Facades\Mail::html($htmlMessage, function ($msg) use ($email) {
                    $msg->to($email)
                        ->subject($this->campaign->title);
                });

            } catch (\Exception $e) {
                $this->logMessage('email', $email, $htmlMessage, 'failed', $e->getMessage());
            }
        }

        // Atualiza Contador
        $this->campaign->increment('processed_count');

        if ($this->campaign->processed_count >= $this->campaign->total_targets) {
            $this->campaign->update(['status' => 'completed']);
        }
    }

    private function logMessage($channel, $recipient, $body, $status, $error = null)
    {
        \App\Models\MessageLog::create([
            'message_campaign_id' => $this->campaign->id,
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
