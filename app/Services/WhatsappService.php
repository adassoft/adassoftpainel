<?php

namespace App\Services;

use App\Models\Configuration;
use Illuminate\Support\Facades\Http;

class WhatsappService
{
    public function loadConfig(): array
    {
        $config = Configuration::where('chave', 'whatsapp_config')->first();

        $defaults = [
            'enabled' => false,
            'access_token' => '',
            'phone_number_id' => '',
            'message_template' => '',
            'automation_secret' => ''
        ];

        if ($config) {
            $json = json_decode($config->valor, true);
            if (is_array($json)) {
                return array_merge($defaults, $json);
            }
        }

        return $defaults;
    }

    public function sanitizeNumber(?string $numero): ?string
    {
        if ($numero === null) {
            return null;
        }

        // 1. Remove tudo que não for dígito
        $digits = preg_replace('/\D+/', '', $numero);

        if ($digits === '') {
            return null;
        }

        // 2. Remove prefixo 55 se existir (para normalizar análise)
        if (str_starts_with($digits, '55') && strlen($digits) >= 12) {
            $digits = substr($digits, 2);
        }

        // 3. Verifica se é celular antigo (10 dígitos: DDD + 8 números)
        // Regra: DDD (2) + Dígito 7, 8 ou 9 (Celular) + 7 dígitos
        if (strlen($digits) === 10) {
            $firstDigit = (int) substr($digits, 2, 1);
            if ($firstDigit >= 7) {
                // Insere o 9
                $digits = substr($digits, 0, 2) . '9' . substr($digits, 2);
            }
        }

        // 4. Se ficou com 11 dígitos ou é fixo (10), adiciona 55
        // Se for menor que 10, é inválido
        if (strlen($digits) < 10) {
            return null;
        }

        return '55' . $digits;
    }

    public function sendMessage(array $config, string $numero, string $mensagem): array
    {
        $numeroSanitizado = $this->sanitizeNumber($numero); // Avoid override argument for logging purposes
        $provider = $config['provider'] ?? 'official';

        // 1. Validations
        if (!($config['enabled'] ?? false)) {
            return ['success' => false, 'error' => 'WhatsApp desabilitado'];
        }

        if (!$numeroSanitizado) {
            // Log Invalid Number attempt
            \App\Models\MessageLog::create([
                'channel' => 'whatsapp',
                'recipient' => $numero, // Original input
                'subject' => 'WhatsApp Message',
                'body' => $mensagem,
                'status' => 'failed',
                'error_message' => 'Número inválido / Sanitização falhou',
                'sent_at' => now()
            ]);
            return ['success' => false, 'error' => 'Número inválido'];
        }

        $result = ['success' => false, 'error' => 'Unknown error'];

        try {
            // --- EVOLUTION API ---
            if ($provider === 'evolution') {
                if (empty($config['evolution_url']) || empty($config['evolution_token'])) {
                    $result = ['success' => false, 'error' => 'Configuração Evolution incompleta'];
                } else {
                    $instance = $config['evolution_instance'] ?? 'Adassoft';
                    $baseUrl = rtrim($config['evolution_url'], '/');
                    $url = "{$baseUrl}/message/sendText/{$instance}";

                    $response = Http::withHeaders([
                        'apikey' => $config['evolution_token'],
                        'Content-Type' => 'application/json'
                    ])->post($url, [
                                'number' => $numeroSanitizado,
                                'text' => $mensagem,
                            ]);

                    if ($response->successful()) {
                        $result = ['success' => true, 'response' => $response->body()];
                    } else {
                        $result = ['success' => false, 'error' => 'Evo Error ' . $response->status() . ': ' . $response->body()];
                    }
                }
            }
            // --- META CLOUD API (OFFICIAL) ---
            elseif ($provider === 'official') { // Elseif explicit to avoid running if provider matched eval but failed config
                if (empty($config['access_token']) || empty($config['phone_number_id'])) {
                    $result = ['success' => false, 'error' => 'Configuração Cloud API incompleta'];
                } else {
                    $url = 'https://graph.facebook.com/v18.0/' . urlencode($config['phone_number_id']) . '/messages';
                    $response = Http::withToken($config['access_token'])
                        ->post($url, [
                            'messaging_product' => 'whatsapp',
                            'to' => $numeroSanitizado,
                            'type' => 'text',
                            'text' => [
                                'preview_url' => false,
                                'body' => $mensagem
                            ]
                        ]);

                    if ($response->successful()) {
                        $result = ['success' => true, 'response' => $response->body()];
                    } else {
                        $result = ['success' => false, 'error' => ' Meta Error ' . $response->status() . ' - ' . $response->body()];
                    }
                }
            } else {
                $result = ['success' => false, 'error' => 'Provider desconhecido'];
            }

        } catch (\Exception $e) {
            $result = ['success' => false, 'error' => $e->getMessage()];
        }

        // 2. Log Result
        \App\Models\MessageLog::create([
            'channel' => 'whatsapp',
            'recipient' => $numeroSanitizado,
            'subject' => 'WhatsApp Message',
            'body' => $mensagem,
            'status' => $result['success'] ? 'sent' : 'failed',
            'error_message' => $result['success'] ? null : ($result['error'] ?? 'Erro desconhecido'),
            'sent_at' => now()
        ]);

        return $result;
    }
}

