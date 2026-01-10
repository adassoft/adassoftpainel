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

