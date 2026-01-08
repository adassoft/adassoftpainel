<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\MercadoLibreConfig;
use App\Models\Company;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Str;

class MercadoLibreController extends Controller
{
    // Redireciona para o ML
    public function auth(Request $request)
    {
        // Aqui assumimos que estamos configurando para a empresa atual ou parâmetro
        // Para simplificar, vamos passar o company_id na sessão ou URL
        $appId = config('services.mercadolibre.app_id'); // Ou pegar do DB
        $redirectUri = route('ml.callback');

        // Se as configs estiverem no banco, melhor:
        $config = MercadoLibreConfig::first(); // Pega a primeira para teste ou use lógica de tenant

        $siteDomain = 'mercadolivre.com.br'; // Padrão BR

        if ($config) {
            $appId = $config->app_id;
            // Ajuste simples de domínio baseado no site_id
            if ($config->site_id === 'MLA')
                $siteDomain = 'mercadolibre.com.ar';
            // Adicionar outros maps se necessário
        }

        if (!$appId) {
            return redirect()->back()->with('error', 'App ID não configurado.');
        }

        // PKCE
        $codeVerifier = Str::random(128);
        session(['ml_code_verifier' => $codeVerifier]);

        $codeChallenge = strtr(rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '='), '+/', '-_');

        $url = "https://auth.{$siteDomain}/authorization?response_type=code&client_id={$appId}&redirect_uri={$redirectUri}&code_challenge={$codeChallenge}&code_challenge_method=S256";

        return redirect($url);
    }

    // Recebe o Code e troca por Token
    public function callback(Request $request)
    {
        $code = $request->query('code');

        if (!$code) {
            return redirect('/admin')->with('error', 'Código de autorização não recebido.');
        }

        // Recupera configs (Assumindo single tenant ou global por enquanto)
        $config = MercadoLibreConfig::first();

        if (!$config) {
            return redirect('/admin')->with('error', 'Configuração não encontrada.');
        }

        $codeVerifier = session('ml_code_verifier');
        session()->forget('ml_code_verifier');

        $response = Http::post('https://api.mercadolibre.com/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $config->app_id,
            'client_secret' => $config->secret_key,
            'code' => $code,
            'redirect_uri' => route('ml.callback'),
            'code_verifier' => $codeVerifier,
        ]);

        if ($response->failed()) {
            Log::error('Erro ML Auth', $response->json());
            return redirect('/admin')->with('error', 'Falha ao autenticar com Mercado Livre.');
        }

        $data = $response->json();

        // Salva tokens
        $config->update([
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'expires_at' => now()->addSeconds($data['expires_in']),
            'ml_user_id' => $data['user_id'],
            'is_active' => true,
        ]);

        return redirect('/admin/mercado-libre-integration')->with('success', 'Conectado com sucesso!');
    }
    // Recebe notificações do ML (Webhooks)
    public function webhook(Request $request)
    {
        // Log para debug inicial
        Log::info('ML Webhook recebido', $request->all());

        // Retorna 200 para o ML não ficar reenviando
        return response()->json(['status' => 'ok']);
    }
}
