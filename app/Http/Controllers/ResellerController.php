<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ResellerController extends Controller
{
    public function index()
    {
        return view('shop.lp-revenda');
    }

    public function register()
    {
        $num1 = rand(1, 10);
        $num2 = rand(1, 10);
        session(['captcha_result' => $num1 + $num2]);

        return view('shop.revenda-cadastro', compact('num1', 'num2'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'login' => 'required|string|unique:usuario,login|max:255',
            'email' => 'required|email|unique:usuario,email|max:255',
            'senha' => 'required|string|min:6|confirmed',
            'cnpj' => 'required|string|unique:usuario,cnpj',
            'razao' => 'nullable|string|max:255',
            'fone' => 'nullable|string|max:20',
            'cep' => 'nullable|string|max:10',
            'endereco' => 'nullable|string|max:255',
            'bairro' => 'nullable|string|max:100',
            'cidade' => 'nullable|string|max:100',
            'uf' => 'required|string|size:2',
            'captcha_challenge' => 'required|numeric',
        ], [
            'login.unique' => 'Este usuário (login) já está sendo utilizado.',
            'email.unique' => 'Este e-mail já está cadastrado em nosso sistema.',
            'email.email' => 'Por favor, informe um endereço de e-mail válido.',
            'cnpj.unique' => 'Este CNPJ/CPF já possui um cadastro de revenda.',
            'senha.confirmed' => 'A confirmação da senha não confere.',
            'senha.min' => 'A senha deve ter pelo menos 6 caracteres.',
            'required' => 'O campo :attribute é obrigatório.',
        ]);

        if ($request->captcha_challenge != session('captcha_result')) {
            return redirect()->back()->withInput()->withErrors(['captcha_challenge' => 'O resultado da soma está incorreto.']);
        }

        $cnpj = preg_replace('/[^0-9]/', '', $request->cnpj);

        // 1. Create Company if not exists
        $company = \App\Models\Company::firstOrCreate(
            ['cnpj' => $cnpj],
            [
                'razao' => $request->razao ?? $request->nome,
                'endereco' => $request->endereco ?? 'Cadastro Web',
                'cidade' => $request->cidade ?? $request->uf,
                'bairro' => $request->bairro ?? 'Centro',
                'cep' => preg_replace('/[^0-9]/', '', $request->cep ?? '00000000'),
                'uf' => $request->uf,
                'fone' => $request->fone,
                'email' => $request->email,
                'data' => now(),
            ]
        );

        // 2. Create User (Reseller)
        \App\Models\User::create([
            'nome' => $request->nome,
            'login' => $request->login,
            'senha' => \Illuminate\Support\Facades\Hash::make($request->senha),
            'data' => now(),
            'uf' => $request->uf,
            'acesso' => '2', // Revenda
            'email' => $request->email,
            'cnpj' => $cnpj,
            'status' => 'Pendente',
        ]);

        return redirect()->back()->with('success', 'Parabéns! Cadastro realizado com sucesso. Seu cadastro está PENDENTE DE APROVAÇÃO.');
    }
}
