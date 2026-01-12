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
        if (!\Illuminate\Support\Facades\Auth::check()) {
            session(['url.intended' => route('checkout.start', $planId)]);
            return redirect()->route('login');
        }

        Log::info("Iniciando Checkout para Plano ID: {$planId}");

        $plan = Plano::with('software')->find($planId);

        if (!$plan) {
            Log::error("Checkout: Plano {$planId} não encontrado no banco.");
            abort(404, 'Plano não encontrado.');
        }

        Log::info("Plano encontrado: {$plan->nome_plano}. Verificando disponibilidade para revenda...");

        // Security check
        if (!$plan->is_ativo_revenda) {
            Log::warning("Checkout: Plano {$planId} não está ativo para a revenda atual.");
            // abort(404, 'Plano indisponível.'); // Temporariamente comentado para teste ou remover se quiser liberar tudo
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

            // Verificar se já existe empresa com este e-mail (evitar duplicação se CNPJ mudou)
            $existingCompany = Empresa::where('email', $request->email)->first();
            if ($existingCompany && $existingCompany->cnpj !== $cnpjLimpo) {
                // E-mail existe em outra empresa. Bloquear ou avisar.
                // Idealmente pedimos login.
                return back()->with('error_register', 'Este e-mail já está cadastrado em outra empresa/CNPJ. Faça login.');
            }

            $empresa = Empresa::firstOrCreate(
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

        if (empty($user->cnpj) || strlen(preg_replace('/\D/', '', $user->cnpj)) !== 14) {
            return redirect()->route('filament.app.pages.my-company')
                ->with('error', 'Por favor, complete/corrija seu CNPJ em "Minha Empresa" antes de prosseguir com a compra.');
        }

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
        $asaasMode = $empresaRevenda ? ($empresaRevenda->asaas_mode ?? 'production') : 'production';

        if (empty($asaasToken)) {
            // Tenta fallback se for Master/Admin, mas idealmente avisa erro
            return back()->with('error', "Erro de Configuração: Revenda do CNPJ {$cnpjRevenda} não possui token de pagamento ativo.");
        }

        $asaas = new AsaasService($asaasToken, $asaasMode);

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

    // ==========================================
    // CHECKOUT PRODUTOS DIGITAIS
    // ==========================================

    public function startDownload($id)
    {
        if (!\Illuminate\Support\Facades\Auth::check()) {
            session(['url.intended' => route('checkout.download.start', $id)]);
            return redirect()->route('login');
        }

        $download = null;
        if (is_numeric($id)) {
            $download = \App\Models\Download::find($id);
        } else {
            $download = \App\Models\Download::where('slug', $id)->firstOrFail();
        }

        if (!$download->is_paid) {
            return redirect()->route('downloads.file', $download->slug ?? $download->id);
        }

        // Verifica se usuário já possui
        if (Auth::check() && Auth::user()->library()->where('download_id', $download->id)->exists()) {
            return redirect()->route('downloads.show', $download->slug ?? $download->id)
                ->with('success', 'Você já possui este produto. O download está liberado!');
        }

        return view('checkout.download-start', compact('download'));
    }

    public function processDownloadPix(Request $request, $id)
    {
        if (!Auth::check()) {
            return redirect()->route('login'); // Middleware deve tratar retorno
        }

        $download = \App\Models\Download::findOrFail($id);
        $user = Auth::user();

        if (empty($user->cnpj) || strlen(preg_replace('/\D/', '', $user->cnpj)) !== 14) {
            return redirect()->route('filament.app.pages.my-company')
                ->with('error', 'Por favor, complete/corrija seu CNPJ em "Minha Empresa" antes de prosseguir com a compra.');
        }

        // Evitar duplicidade de pendentes (Opcional, mas boa prática)
        // Por simplificação, vamos gerar um novo sempre.

        // Token da Revenda/Empresa
        $cnpjRevenda = \App\Services\ResellerBranding::getCurrentCnpj();
        $empresaRevenda = Empresa::where('cnpj', $cnpjRevenda)->first();
        // Em dev local, $asaasToken pode ser null se não configurado
        $asaasToken = $empresaRevenda ? $empresaRevenda->asaas_access_token : env('ASAAS_ACCESS_TOKEN');
        $asaasMode = $empresaRevenda ? ($empresaRevenda->asaas_mode ?? 'production') : 'production';

        if (!$asaasToken) {
            return back()->with('error', 'Erro interno: Configuração de pagamento não localizada.');
        }

        $asaas = new AsaasService($asaasToken, $asaasMode);

        try {
            $customerId = $asaas->createCustomer($user);

            $valorFinal = $download->preco;
            if ($valorFinal <= 0) {
                // Se for grátis por algum erro de lógica, libera direto
                \App\Models\UserLibrary::firstOrCreate([
                    'user_id' => $user->id,
                    'download_id' => $download->id
                ]);
                return redirect()->route('downloads.show', $download->slug ?? $download->id);
            }

            $externalRef = "DL-{$download->id}-USER-{$user->id}-TS-" . time();

            $pixData = $asaas->createPixCharge(
                $customerId,
                $valorFinal,
                "Produto Digital: {$download->titulo}",
                $externalRef
            );

            // Criar Order
            $order = \App\Models\Order::create([
                'user_id' => $user->id,
                // Legacy fields for compatibility
                'status' => 'pending',
                'valor' => $valorFinal,
                'cnpj_revenda' => $cnpjRevenda,
                'asaas_payment_id' => $pixData->id,
                'external_reference' => $externalRef,

                // New Fields
                'total' => $valorFinal,
                'payment_method' => 'pix',
                'external_id' => $pixData->id,
                'payment_url' => $pixData->payload,
            ]);

            // Criar Item
            \App\Models\OrderItem::create([
                'order_id' => $order->id,
                'download_id' => $download->id,
                'product_name' => $download->titulo,
                'price' => $valorFinal
            ]);

            // Reutiliza a view de PIX existente, passando dados extras
            return view('checkout.pix', [
                'pixData' => $pixData,
                'pageTitle' => 'Pagamento - ' . $download->titulo,
                'itemName' => $download->titulo,
                'downloadSlug' => $download->slug ?? $download->id // Para botão voltar
            ]);

        } catch (\Exception $e) {
            Log::error("Download Checkout Error: " . $e->getMessage());
            return back()->with('error', 'Ops! Erro ao gerar pagamento: ' . $e->getMessage());
        }
    }
}
