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
        $originalHost = request()->getHost();
        $host = str_replace('www.', '', strtolower($originalHost));

        // Debug: Logando a busca
        // \Illuminate\Support\Facades\Log::info("ResellerBranding: Buscando config para host: {$host}");

        return Cache::remember("site_config_obj_{$host}", 3600, function () use ($host) {
            $configs = ResellerConfig::with(['user.empresa']) // Eager Load Empresa
                ->where('ativo', true)
                ->where('status_aprovacao', 'aprovado')
                ->where('dominios', 'LIKE', "%{$host}%")
                ->get();

            foreach ($configs as $config) {
                $domainsList = array_map(function ($d) {
                    return str_replace('www.', '', trim(strtolower($d)));
                }, explode(',', $config->dominios));

                if (in_array($host, $domainsList)) {
                    // \Illuminate\Support\Facades\Log::info("ResellerBranding: Encontrado por DOMÍNIO: ID {$config->id}");
                    return $config;
                }
            }

            // Se não encontrou configuração específica para o domínio, busca a Padrão
            $default = ResellerConfig::with(['user.empresa'])
                ->where('ativo', true)
                ->where('status_aprovacao', 'aprovado')
                ->where('is_default', true)
                ->first();

            if ($default) {
                // \Illuminate\Support\Facades\Log::info("ResellerBranding: Usando PADRÃO: ID {$default->id}");
            } else {
                // \Illuminate\Support\Facades\Log::info("ResellerBranding: Nenhuma config encontrada.");
            }

            return $default;
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
            // \Illuminate\Support\Facades\Log::warning("ResellerBranding: Falha ao obter empresa. Config: " . ($config ? 'OK' : 'NULL') . ", User: " . ($config?->user ? 'OK' : 'NULL') . ", Empresa: " . ($config?->user?->empresa ? 'OK' : 'NULL'));
            return [
                'has_payment' => false,
                'whatsapp' => null,
                'email' => null
            ];
        }

        $empresa = $config->user->empresa;
        $hasPayment = !empty($empresa->asaas_access_token);

        // \Illuminate\Support\Facades\Log::info("ResellerBranding: Pagamento para revenda {$config->id}: " . ($hasPayment ? 'SIM' : 'NÃO'));

        return [
            'has_payment' => $hasPayment,
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
            'icone_url' => $config->icone_path ? Storage::url($config->icone_path) : asset('favicon.svg'),
            'cor_start' => $config->cor_primaria_gradient_start ?? '#4e73df',
            'cor_end' => $config->cor_primaria_gradient_end ?? '#224abe',
            'cor_accent' => $config->cor_acento ?? ($config->cor_primaria_gradient_start ?? '#4e73df'),
            'cor_secondary' => $config->cor_secundaria ?? '#858796',
            'google_analytics_id' => $config->google_analytics_id,
            'facebook_pixel_id' => $config->facebook_pixel_id,
            'microsoft_clarity_id' => $config->microsoft_clarity_id,
        ];
    }

    /**
     * Verifica se a configuração atual é a padrão do sistema (Master Reseller) ou se é acesso direto.
     */
    public static function isDefault(): bool
    {
        $config = self::getConfig();
        // Se não tem config, assume que é o sistema raiz (AdasSoft), então é "Default"
        if (!$config) {
            return true;
        }
        return (bool) $config->is_default;
    }
}
