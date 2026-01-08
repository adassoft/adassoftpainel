<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use App\Models\SeoEntry;

class SeoService
{
    /**
     * Tenta resolver os metadados de SEO para a requisição atual.
     * Prioridade:
     * 1. SeoEntry específico da rota (url_path)
     * 2. SeoEntry do Model vinculado (se estivermos em uma página de show)
     * 3. Configuração Global
     * 4. Defaults Hardcoded
     */
    public static function getMeta()
    {
        $path = '/' . request()->path(); // ex: /produto/pdv-delphi
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        // 1. Check Path estático
        $entry = SeoEntry::where('url_path', $path)->first();

        // 2. Check Model Vinculado (se não achou por path)
        if (!$entry) {
            $route = Route::current();
            // Verifica se tem parâmetros de rota que são Models com HasSeo
            foreach ($route->parameters() as $param) {
                if (is_object($param) && in_array(\App\Models\Concerns\HasSeo::class, class_uses_recursive($param))) {
                    $entry = $param->seo;
                    break;
                }
            }
        }

        // 3. Config Global
        $config = json_decode(\App\Models\Configuration::where('chave', 'seo_config')->value('valor') ?? '{}', true);

        // Monta o objeto final
        return (object) [
            'title' => $entry?->title ?? $config['site_title'] ?? config('app.name'),
            'description' => $entry?->description ?? $config['site_description'] ?? 'Soluções em Software.',
            'keywords' => $entry?->keywords ?? $config['keywords'] ?? '',
            'robots' => $entry?->robots ?? 'index, follow',
            'canonical' => $entry?->canonical_url ?? ($entry ? url($path) : url()->current()),
            'image' => $entry?->og_image ? asset($entry->og_image) : ($config['default_image'] ?? asset('favicon.svg')),
            'site_name' => $config['site_name'] ?? config('app.name'),
            'twitter_handle' => $config['twitter_handle'] ?? '@adassoft',
            'json_ld' => $entry?->json_ld ?? null,
        ];
    }
}
