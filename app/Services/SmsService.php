<?php

namespace App\Services;

use App\Models\Configuration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function loadConfig(): array
    {
        $config = Configuration::where('chave', 'sms_config')->first();

        $defaults = [
            'enabled' => false,
            'provider' => 'generic',
            'api_url' => '',
            'api_key' => '',
            'param_phone' => 'phone',
            'param_message' => 'message',
        ];

        if ($config) {
            $json = json_decode($config->valor, true);
            if (is_array($json)) {
                return array_merge($defaults, $json);
            }
        }

        return $defaults;
    }

    public function sendSms(array $config, string $numero, string $mensagem): array
    {
        if (!($config['enabled'] ?? false)) {
            return ['success' => false, 'error' => 'SMS desabilitado.'];
        }

        $numero = preg_replace('/\D/', '', $numero);

        if (empty($config['api_url'])) {
            return ['success' => false, 'error' => 'URL da API não configurada.'];
        }

        // Exemplo Genérico (GET ou POST)
        // A maioria dos gateways brasileiros aceita um GET simples ou POST json
        try {
            $response = Http::timeout(10)->post($config['api_url'], [
                $config['param_phone'] ?? 'phone' => $numero,
                $config['param_message'] ?? 'message' => $mensagem,
                'key' => $config['api_key'] ?? '',
            ]);

            $success = $response->successful();
            $result = $success ? ['success' => true, 'response' => $response->body()] : ['success' => false, 'error' => 'HTTP ' . $response->status()];

        } catch (\Exception $e) {
            $result = ['success' => false, 'error' => $e->getMessage()];
        }

        // Log SMS
        \App\Models\MessageLog::create([
            'channel' => 'sms',
            'recipient' => $numero,
            'subject' => 'SMS Notification',
            'body' => $mensagem,
            'status' => $result['success'] ? 'sent' : 'failed',
            'error_message' => $result['success'] ? null : ($result['error'] ?? 'Erro desconhecido'),
            'sent_at' => now()
        ]);

        return $result;
    }
}
