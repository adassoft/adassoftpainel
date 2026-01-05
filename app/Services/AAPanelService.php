<?php

namespace App\Services;

use App\Models\Configuration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AAPanelService
{
    protected $config;

    public function __construct()
    {
        $setting = Configuration::where('chave', 'aapanel_config')->first();
        $this->config = $setting ? (is_array($setting->valor) ? $setting->valor : json_decode($setting->valor, true)) : [];
    }

    public function adicionarDominio($dominioCompleto)
    {
        // Se não houver config, assumimos que a integração está desativada e retornamos sucesso
        // para não bloquear o fluxo de aprovação no painel.
        if (empty($this->config) || empty($this->config['url'])) {
            return ['success' => true, 'msg' => 'Integração aaPanel não configurada (Skipped).'];
        }

        $url = rtrim($this->config['url'] ?? '', '/');
        $key = $this->config['key'] ?? '';

        if (!$url || !$key) {
            return ['success' => false, 'msg' => 'URL ou Key do aaPanel inválidos.'];
        }

        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'http://' . $url;
        }

        $sitePrincipal = $this->config['main_domain'] ?? 'express.adassoft.com';
        $siteId = $this->getSiteId($url, $key, $sitePrincipal);

        $now = time();
        $token = md5($now . md5($key));

        try {
            $response = Http::withoutVerifying()
                ->asForm()
                ->post($url . '/site?action=AddDomain', [
                    'id' => $siteId,
                    'webname' => $sitePrincipal,
                    'domain' => $dominioCompleto,
                    'request_token' => $token,
                    'request_time' => $now,
                ]);

            $result = $response->json();

            if ($response->successful() && ($result['status'] ?? false) === true) {
                return ['success' => true, 'msg' => "Domínio $dominioCompleto adicionado com sucesso."];
            }

            Log::warning("AAPanel Domain Add Failed: " . json_encode($result));
            return ['success' => false, 'msg' => $result['msg'] ?? 'Erro desconhecido no aaPanel.'];
        } catch (\Exception $e) {
            Log::error("AAPanel Connection Error: " . $e->getMessage());
            return ['success' => false, 'msg' => 'Conexão falhou: ' . $e->getMessage()];
        }
    }

    protected function getSiteId($url, $key, $siteName)
    {
        $now = time();
        $token = md5($now . md5($key));

        try {
            $response = Http::withoutVerifying()
                ->asForm()
                ->post($url . '/data?action=getData&table=sites', [
                    'table' => 'sites',
                    'limit' => 100,
                    'search' => $siteName,
                    'request_token' => $token,
                    'request_time' => $now,
                ]);

            $result = $response->json();

            if (isset($result['data']) && is_array($result['data'])) {
                foreach ($result['data'] as $site) {
                    if ($site['name'] === $siteName) {
                        return $site['id'];
                    }
                }
            }

            return $result['data'][0]['id'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
