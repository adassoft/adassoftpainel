<?php

namespace App\Services;

use App\Models\Configuration;
use Illuminate\Support\Facades\Http;

class GeminiService
{
    public function generateContent(string $prompt): array
    {
        $config = Configuration::where('chave', 'google_config')->first();

        if (!$config) {
            return ['success' => false, 'error' => 'Configuração do Google não encontrada.'];
        }

        $configData = json_decode($config->valor, true);
        $apiKey = $configData['gemini_api_key'] ?? '';
        $model = $configData['gemini_model'] ?? 'gemini-1.5-flash';

        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'API Key do Gemini não configurada.'];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $apiKey;

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])
                ->withoutVerifying() // Match legacy behavior
                ->post($url, [
                    "contents" => [
                        [
                            "parts" => [
                                ["text" => $prompt]
                            ]
                        ]
                    ]
                ]);

            if ($response->successful()) {
                $result = $response->json();
                if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                    return ['success' => true, 'reply' => $result['candidates'][0]['content']['parts'][0]['text']];
                }
                return ['success' => false, 'error' => 'Resposta inválida da API.', 'full_response' => $result];
            }

            $errorMsg = $response->json()['error']['message'] ?? 'Erro desconhecido na API.';
            return ['success' => false, 'error' => 'Google API Error: ' . $errorMsg];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Erro de conexão: ' . $e->getMessage()];
        }
    }
}
