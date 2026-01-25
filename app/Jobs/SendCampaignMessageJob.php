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
    protected $target;


    public function __construct(\App\Models\MessageCampaign $campaign, $target)
    {
        $this->campaign = $campaign;
        $this->target = $target; // Can be License or Lead
    }

    public function handle(): void
    {
        if (!$this->target)
            return;

        $name = '';
        $companyName = '';
        $softwareName = '';
        $phone = null;
        $email = null;

        if ($this->target instanceof \App\Models\Lead) {
            $name = $this->target->nome;
            $companyName = $this->target->empresa;
            $softwareName = $this->target->download->titulo ?? 'Download';
            $phone = $this->target->whatsapp;
            $email = $this->target->email;
        } elseif ($this->target instanceof \App\Models\License) {
            if (!$this->target->company)
                return;
            $company = $this->target->company;
            $name = $company->razao;
            $companyName = $company->razao;
            $softwareName = $this->target->nome_software;
            $phone = $company->fone;
            $email = $company->email;
        } else {
            // Fallback for generic objects if valid
            return;
        }

        // 1. Preparar Variáveis
        $vars = [
            '{name}' => $name ?? 'Cliente',
            '{first_name}' => explode(' ', $name ?? 'Cliente')[0],
            '{company}' => $companyName ?? 'Sua Empresa',
            '{software}' => $softwareName ?? 'Software',
        ];

        // Mensagem Base
        $rawMessage = $this->campaign->message;

        // Versão HTML (Para Email)
        $htmlMessage = str_replace(array_keys($vars), array_values($vars), $rawMessage);

        // Versão Texto (Para Zap/SMS) - Converter HTML em Texto
        $textOnly = str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $rawMessage);
        $textOnly = strip_tags($textOnly);
        $textOnly = html_entity_decode($textOnly);
        $textMessage = str_replace(array_keys($vars), array_values($vars), $textOnly);
        $textMessage = preg_replace("/\n\s*\n\s*\n/", "\n\n", $textMessage);
        $textMessage = trim($textMessage);

        // Extrair Contatos
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
                $waService->sendMessage($waConfig, $cleanPhone, $textMessage, $this->campaign->id);
            } catch (\Exception $e) {
                // Failures outside service (e.g. config load)
                $this->createLog('whatsapp', $phone, $textMessage, 'failed', $e->getMessage());
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
                $smsService->sendSms($smsConfig, $cleanPhone, $textMessage, $this->campaign->id);
            } catch (\Exception $e) {
                $this->createLog('sms', $phone, $textMessage, 'failed', $e->getMessage());
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

                // Log Email Success
                $this->createLog('email', $email, $htmlMessage, 'sent');

            } catch (\Exception $e) {
                $this->createLog('email', $email, $htmlMessage, 'failed', $e->getMessage());
            }
        }

        // Atualiza Contador
        $this->campaign->increment('processed_count');

        if ($this->campaign->processed_count >= $this->campaign->total_targets) {
            $this->campaign->update(['status' => 'completed']);
        }
    }

    private function createLog($channel, $recipient, $body, $status, $error = null)
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
