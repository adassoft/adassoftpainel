<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function show($id)
    {

        // === Branding Dinâmico ===
        $currentBranding = \App\Services\ResellerBranding::getCurrent();
        $contactInfo = \App\Services\ResellerBranding::getContactInfo();

        $branding = [
            'nome_sistema' => $currentBranding['nome_sistema'] ?? 'AdasSoft Store',
            'email_suporte' => $contactInfo['email'] ?? 'suporte@adassoft.com',
            'whatsapp' => $contactInfo['whatsapp'] ?? '(11) 99999-9999',
            'logo_path' => $currentBranding['logo_url'] ?? asset('favicon.svg'),
        ];

        // === Contexto de Revenda ===
        $cnpjCtx = \App\Services\ResellerBranding::getCurrentCnpj();
        $isPlatform = (!$cnpjCtx || $cnpjCtx === '00000000000100');

        $query = \App\Models\Software::with([
            'plans' => function ($q) use ($isPlatform, $cnpjCtx) {
                // Filtro Base
                $q->where(function ($sub) {
                    $sub->where('status', '!=', 'inativo')
                        ->orWhereNull('status');
                })->orderBy('valor', 'asc');

                // Filtro de Revenda
                if (!$isPlatform) {
                    $q->whereExists(function ($sub) use ($cnpjCtx) {
                        $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                            ->from('planos_revenda')
                            ->whereColumn('planos_revenda.plano_id', 'planos.id')
                            ->where('planos_revenda.cnpj_revenda', $cnpjCtx)
                            ->where('planos_revenda.ativo', 1);
                    });
                }
            }
        ]);

        if (is_numeric($id)) {
            $product = $query->where('id', $id)->firstOrFail();
        } else {
            $product = $query->where('slug', $id)->firstOrFail();
        }

        // === Ajuste de Preços (Override da Revenda) ===
        $plans = $product->plans->map(function ($plan) use ($isPlatform, $cnpjCtx) {
            if (!$isPlatform) {
                $config = \App\Models\PlanoRevenda::where('plano_id', $plan->id)
                    ->where('cnpj_revenda', $cnpjCtx)
                    ->first();

                if ($config && $config->valor_venda > 0) {
                    $plan->valor = $config->valor_venda;
                }
            }
            return $plan;
        });

        return view('products.show', compact('product', 'plans', 'branding'));
    }
}
