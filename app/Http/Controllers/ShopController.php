<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Software;
use App\Models\Company;
use App\Http\Controllers\Controller;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $cnpjRevenda = $request->query('r');
        $currentBranding = \App\Services\ResellerBranding::getCurrent();
        $contactInfo = \App\Services\ResellerBranding::getContactInfo();

        $branding = [
            'nome_sistema' => $currentBranding['nome_sistema'] ?? 'AdasSoft Store',
            'email_suporte' => $contactInfo['email'] ?? 'suporte@adassoft.com',
            'whatsapp' => $contactInfo['whatsapp'] ?? '(11) 99999-9999',
            'logo_path' => $currentBranding['logo_url'] ?? asset('favicon.svg'),
            'tem_asaas' => $contactInfo['has_payment'] ?? false
        ];

        // Se for revenda via query param (legado), tenta buscar dados
        if ($cnpjRevenda) {
            $revenda = Company::where('cnpj', $cnpjRevenda)->where('status', 'Ativo')->first();
            if ($revenda) {
                $nomeRevenda = $revenda->nome_fantasia ?: $revenda->razao_social;
                $branding['nome_sistema'] = $nomeRevenda;
            }
        }

        // 1. Categorias
        $categorias = Software::where('status', 1)
            ->whereNotNull('categoria')
            ->where('categoria', '!=', '')
            ->distinct()
            ->orderBy('categoria')
            ->pluck('categoria');

        // 2. Produtos
        $cnpjCtx = \App\Services\ResellerBranding::getCurrentCnpj();
        $isPlatform = (!$cnpjCtx || $cnpjCtx === '00000000000100'); // Ajuste conforme CNPJ padrao do sistema

        $query = Software::with([
            'plans' => function ($q) use ($isPlatform, $cnpjCtx) {
                $q->orderBy('valor');

                // Se for revenda, só mostra planos ativados explicitamente na tabela de revenda
                // Se for plataforma, mostra todos os planos globais
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
        ])->where('status', 1);

        $produtos = $query->orderBy('nome_software')->get();

        // Calcular 'A partir de' e remover produtos sem planos visíveis para esta revenda
        $produtos = $produtos->filter(function ($prod) {
            return $prod->plans->isNotEmpty();
        })->map(function ($prod) use ($cnpjCtx, $isPlatform) {

            // Calcula o preço correto (Revenda pode ter override de preço)
            foreach ($prod->plans as $plan) {
                if (!$isPlatform) {
                    $config = \App\Models\PlanoRevenda::where('plano_id', $plan->id)
                        ->where('cnpj_revenda', $cnpjCtx)
                        ->first();
                    if ($config && $config->valor_venda > 0) {
                        $plan->valor = $config->valor_venda;
                    }
                }
            }

            $minPrice = $prod->plans->min('valor');
            $prod->min_price = $minPrice;
            return $prod;
        });

        return view('shop.index', compact('categorias', 'produtos', 'branding', 'cnpjRevenda'));
    }
}
