<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Plano;

use App\Services\ResellerBranding;
use App\Services\AsaasService;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Empresa;

class CheckoutController extends Controller
{
    public function start($planId)
    {
        $plan = Plano::with('software')->findOrFail($planId);

        // Security check
        if (!$plan->is_ativo_revenda) {
            abort(404, 'Plano indisponível.');
        }

        return view('checkout.index', compact('plan'));
    }

    public function authenticate(Request $request)
    {
        $action = $request->input('action');
        $planId = $request->input('plan_id');

        if ($action === 'login') {
            if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                return redirect()->route('checkout.start', $planId);
            }
            return back()->with('error_login', 'E-mail ou senha incorretos.');
        }

        if ($action === 'register') {
            $request->validate([
                'nome' => 'required',
                'email' => 'required|email|unique:usuario,email',
                'password' => 'required|min:8',
                'cnpj' => 'required',
                'uf' => 'required'
            ]);

            // Lógica de Cadastro (Simplificada)
            $cnpjLimpo = preg_replace('/[^0-9]/', '', $request->cnpj);
            $config = ResellerBranding::getConfig();
            $cnpjRep = $config && $config->user ? $config->user->cnpj : null;

            Empresa::firstOrCreate(
                ['cnpj' => $cnpjLimpo],
                [
                    'razao' => $request->razao ?? $request->nome, // Usa Razão se informado, senão Nome
                    'fone' => $request->fone ?? '',
                    'cidade' => $request->cidade ?? 'Cadastro Rápido',
                    'uf' => $request->uf,
                    'email' => $request->email,
                    'data' => now(),
                    'status' => 'Ativo',
                    'cnpj_representante' => $cnpjRep,
                    'endereco' => 'Cadastro Online',
                    'bairro' => 'Centro',
                    'cep' => '00000000'
                ]
            );

            $user = User::create([
                'nome' => $request->nome,
                'email' => $request->email,
                'login' => $request->email, // Login = Email
                'senha' => Hash::make($request->password),
                'cnpj' => $cnpjLimpo,
                'acesso' => 3,
                'uf' => $request->uf,
                'data' => now(),
                'status' => 'Ativo'
            ]);

            Auth::login($user);
            return redirect()->route('checkout.start', $planId);
        }

        return back();
    }

    public function processPix(Request $request, $planId)
    {
        if (!Auth::check()) {
            return redirect()->route('checkout.start', $planId)->with('error', 'Por favor, faça login ou cadastre-se.');
        }

        $plan = Plano::findOrFail($planId);
        $user = auth()->user();

        // 1. Identificar Revenda e Contexto (Renovação vs Novo)
        $licenseId = $request->input('license_id');
        $cnpjRevenda = null;
        $externalRefSuffix = "";

        if ($licenseId) {
            // RENOVAÇÃO: Usa a revenda da licença original
            $license = \App\Models\License::findOrFail($licenseId);

            // Segurança: Validar se software bate
            if ($license->software_id != $plan->software_id) {
                return back()->with('error', 'O plano selecionado não corresponde ao software da licença.');
            }

            $cnpjRevenda = $license->cnpj_revenda;
            $externalRefSuffix = "-LIC-{$licenseId}";
        } else {
            // NOVA VENDA: Usa a revenda do painel atual
            $cnpjRevenda = \App\Services\ResellerBranding::getCurrentCnpj();
        }

        // 2. Buscar Token Asaas da Revenda
        $empresaRevenda = Empresa::where('cnpj', $cnpjRevenda)->first();
        $asaasToken = $empresaRevenda ? $empresaRevenda->asaas_access_token : null;

        if (empty($asaasToken)) {
            // Tenta fallback se for Master/Admin, mas idealmente avisa erro
            return back()->with('error', "Erro de Configuração: Revenda do CNPJ {$cnpjRevenda} não possui token de pagamento ativo.");
        }

        $asaas = new AsaasService($asaasToken);

        try {
            $customerId = $asaas->createCustomer($user);

            // Nota: Se estiver renovando licença de outra revenda em painel diferente, preço pode variar.
            // Assumimos aqui que o preço exibido ($plan->preco_final) é o que será cobrado.
            $valorFinal = $plan->preco_final;

            $externalRef = "PLAN-{$plan->id}-USER-{$user->id}{$externalRefSuffix}-TS-" . time();

            $pixData = $asaas->createPixCharge(
                $customerId,
                $valorFinal,
                "Assinatura {$plan->software->nome_software}",
                $externalRef
            );

            // Persistir intenção de compra
            \App\Models\Order::create([
                'user_id' => $user->id,
                'plano_id' => $plan->id,
                'asaas_payment_id' => $pixData->id,
                'external_reference' => $externalRef,
                'status' => 'pending',
                'valor' => $valorFinal,
                'cnpj_revenda' => $cnpjRevenda,
                'licenca_id' => $licenseId
            ]);

            return view('checkout.pix', compact('plan', 'pixData'));

        } catch (\Exception $e) {
            Log::error("Checkout Error: " . $e->getMessage());
            return back()->with('error', 'Erro ao processar pagamento: ' . $e->getMessage());
        }
    }
}
