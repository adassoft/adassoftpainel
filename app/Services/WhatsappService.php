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

        $digits = preg_replace('/\D+/', '', $numero);
        if ($digits === '') {
            return null;
        }

        // Se tiver 10 ou 11 dígitos, assume que é BR e adiciona 55
        if ((strlen($digits) === 10 || strlen($digits) === 11) && substr($digits, 0, 2) !== '55') {
            $digits = '55' . $digits;
        }

        if (strlen($digits) < 10) {
            return null;
        }

        return $digits;
    }

    public function sendMessage(array $config, string $numero, string $mensagem): array
    {
        $numero = $this->sanitizeNumber($numero);
        $provider = $config['provider'] ?? 'official';

        if (!($config['enabled'] ?? false)) {
            return ['success' => false, 'error' => 'WhatsApp desabilitado'];
        }

        if (!$numero) {
            return ['success' => false, 'error' => 'Número inválido'];
        }

        try {
            // --- EVOLUTION API ---
            if ($provider === 'evolution') {
                if (empty($config['evolution_url']) || empty($config['evolution_token'])) {
                    return ['success' => false, 'error' => 'Configuração Evolution incompleta'];
                }

                $instance = $config['evolution_instance'] ?? 'Adassoft';
                $baseUrl = rtrim($config['evolution_url'], '/');
                $url = "{$baseUrl}/message/sendText/{$instance}";

                // Formato padrão da Evolution v2 (POST /message/sendText/{instance})
                $response = Http::withHeaders([
                    'apikey' => $config['evolution_token'],
                    'Content-Type' => 'application/json'
                ])->post($url, [
                            'number' => $numero, // Evolution geralmente aceita '5511...' sem @s.whatsapp.net na v2
                            'text' => $mensagem,
                            // 'delay' => 1200, // opcional
                        ]);

                if ($response->successful()) {
                    return ['success' => true, 'response' => $response->body()];
                }
                return ['success' => false, 'error' => 'Evo Error ' . $response->status() . ': ' . $response->body()];
            }

            // --- META CLOUD API (OFFICIAL) ---
            if (empty($config['access_token']) || empty($config['phone_number_id'])) {
                return ['success' => false, 'error' => 'Configuração Cloud API incompleta'];
            }

            $url = 'https://graph.facebook.com/v18.0/' . urlencode($config['phone_number_id']) . '/messages';

            $response = Http::withToken($config['access_token'])
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'to' => $numero,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => $mensagem
                    ]
                ]);

            if ($response->successful()) {
                return ['success' => true, 'response' => $response->body()];
            }

            return ['success' => false, 'error' => ' Meta Error ' . $response->status() . ' - ' . $response->body()];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

