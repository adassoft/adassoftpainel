<?php

namespace App\Services;

use App\Models\Configuration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleAnalyticsService
{
    /**
     * Envia evento de compra (purchase) via Measurement Protocol do GA4.
     * Útil para registrar vendas que ocorrem no backend (Webhooks, Renovações internas).
     *
     * @param array $params [transaction_id, value, user_id, items]
     * @return void
     */
    public static function sendPurchaseEvent(array $params)
    {
        $config = Configuration::where('chave', 'google_config')->first();
        if (!$config)
            return;

        $gData = json_decode($config->valor, true);
        $measurementId = $gData['ga_measurement_id'] ?? null;
        $apiSecret = $gData['ga_api_secret'] ?? null;

        // Só executa se tiver as credenciais configuradas
        if (!$measurementId || !$apiSecret) {
            return;
        }

        $url = "https://www.google-analytics.com/mp/collect?measurement_id={$measurementId}&api_secret={$apiSecret}";

        // Tenta obter Client ID do cookie _ga presente na requisição (se houver)
        // O formato geralmente é GA1.2.123456789.123456789. Precisamos da parte numérica final.
        $cid = request()->cookie('_ga');

        if ($cid && strpos($cid, 'GA') === 0) {
            $parts = explode('.', $cid);
            // Remove o prefixo (GA1.2) e pega o resto
            if (count($parts) > 2) {
                $cid = implode('.', array_slice($parts, 2));
            }
        }

        // Fallback: Se não tiver cookie (chamada API/Webhook pura), usa o ID do usuário
        if (!$cid) {
            $cid = 'UID-' . ($params['user_id'] ?? uniqid());
        }

        $payload = [
            'client_id' => $cid,
            'user_id' => (string) ($params['user_id'] ?? ''),
            'non_personalized_ads' => false,
            'events' => [
                [
                    'name' => 'purchase',
                    'params' => [
                        'transaction_id' => (string) $params['transaction_id'],
                        'value' => (float) $params['value'],
                        'currency' => 'BRL',
                        'tax' => 0.00,
                        'shipping' => 0.00,
                        'items' => $params['items'] ?? []
                    ]
                ]
            ]
        ];

        // Dispara assincronamente (sem esperar resposta para não travar o usuário)
        // Em Laravel Http::post é síncrono por padrão, mas é rápido.
        try {
            Http::timeout(2)->post($url, $payload);
        } catch (\Exception $e) {
            Log::error('GA4 Measurement Protocol Error: ' . $e->getMessage());
        }
    }
}
