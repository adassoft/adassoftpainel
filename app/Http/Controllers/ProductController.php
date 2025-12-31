<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function show($id)
    {
        $branding = [
            'nome_sistema' => 'AdasSoft Store',
            'email_suporte' => 'suporte@adassoft.com',
            'whatsapp' => '(11) 99999-9999',
            'logo_path' => asset('favicon.svg'),
        ];

        $product = \App\Models\Software::with([
            'plans' => function ($query) {
                $query->where('status', '!=', 'inativo')
                    ->orWhereNull('status')
                    ->orderBy('valor', 'asc');
            }
        ])->findOrFail($id);

        $plans = $product->plans;

        return view('products.show', compact('product', 'plans', 'branding'));
    }
}
