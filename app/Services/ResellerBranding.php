<?php

namespace App\Services;

use App\Models\ResellerConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ResellerBranding
{
    /**
     * Recupera as configurações de branding baseadas no domínio atual.
     * Retorna null se não for um domínio de revenda ou se não houver config aprovada.
     */
    /**
     * Recupera o objeto ResellerConfig completo para o domínio atual.
     */
    public static function getConfig()
    {
        $host = request()->getHost();

        return Cache::remember("site_config_obj_{$host}", 3600, function () use ($host) {
            $configs = ResellerConfig::with('user') // Carrega o User para pegar o CNPJ
                ->where('ativo', true)
                ->where('status_aprovacao', 'aprovado')
                ->where('dominios', 'LIKE', "%{$host}%")
                ->get();

            foreach ($configs as $config) {
                $domainsList = array_map('trim', explode(',', strtolower($config->dominios)));
                if (in_array(strtolower($host), $domainsList)) {
                    return $config;
                }
            }
            return null;
        });
    }

    /**
     * Retorna o CNPJ da revenda atual.
     */
    public static function getCurrentCnpj(): ?string
    {
        $config = self::getConfig();
        return $config?->user?->cnpj;
    }

    /**
     * Retorna informações de contato e status de pagamento da revenda.
     */
    public static function getContactInfo()
    {
        $config = self::getConfig();

        // Se não tem revenda (acesso direto ou erro), assume sem pagamento configurado
        if (!$config || !$config->user || !$config->user->empresa) {
            return [
                'has_payment' => false,
                'whatsapp' => null,
                'email' => null
            ];
        }

        $empresa = $config->user->empresa;

        return [
            'has_payment' => !empty($empresa->asaas_access_token),
            // Prioriza Celular, depois Fone. Limpa caracteres não numéricos.
            'whatsapp' => preg_replace('/[^0-9]/', '', $empresa->celular ?? $empresa->fone ?? ''),
            'email' => $empresa->email,
        ];
    }

    /**
     * Recupera as configurações visuais de branding.
     */
    public static function getCurrent()
    {
        $config = self::getConfig();

        if (!$config) {
            return null;
        }

        return [
            'nome_sistema' => $config->nome_sistema,
            'slogan' => $config->slogan,
            'logo_url' => $config->logo_path ? Storage::url($config->logo_path) : null,
            'gradient_start' => $config->cor_primaria_gradient_start,
            'gradient_end' => $config->cor_primaria_gradient_end,
        ];
    }
}
