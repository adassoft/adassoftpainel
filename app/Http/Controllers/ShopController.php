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
        $branding = [
            'nome_sistema' => 'AdasSoft Store',
            'email_suporte' => 'suporte@adassoft.com',
            'whatsapp' => '(11) 99999-9999',
            'logo_path' => asset('favicon.svg'), // Placeholder
            'tem_asaas' => true // Default
        ];

        // Se for revenda, tenta buscar dados
        if ($cnpjRevenda) {
            $revenda = Company::where('cnpj', $cnpjRevenda)->where('status', 'Ativo')->first();
            if ($revenda) {
                $nomeRevenda = $revenda->nome_fantasia ?: $revenda->razao_social;
                $branding['nome_sistema'] = $nomeRevenda;
            }
        }

        // 1. Categorias
        $categorias = Software::where('status', 1)
            ->distinct()
            ->orderBy('categoria')
            ->pluck('categoria');

        // 2. Produtos
        $query = Software::with([
            'plans' => function ($q) {
                $q->orderBy('valor');
            }
        ])->where('status', 1);

        $produtos = $query->orderBy('nome_software')->get();

        // Calcular 'A partir de' para cada produto na memória
        $produtos = $produtos->map(function ($prod) {
            // Em Laravel puro, a collection de plans é uma Collection do Eloquent
            $minPrice = $prod->plans->where('status', 1)->min('valor');
            // Se não tiver status na tabela plans (como vi antes), assume ativo.
            if (is_null($minPrice)) {
                $minPrice = $prod->plans->min('valor');
            }
            $prod->min_price = $minPrice;
            return $prod;
        });

        return view('shop.index', compact('categorias', 'produtos', 'branding', 'cnpjRevenda'));
    }
}
