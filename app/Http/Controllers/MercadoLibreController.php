<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\MercadoLibreConfig;
use App\Models\Company;
use Illuminate\Support\Facades\Log;

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
        if ($config) {
            $appId = $config->app_id;
        }

        if (!$appId) {
            return redirect()->back()->with('error', 'App ID não configurado.');
        }

        $url = "https://auth.mercadolivre.com.br/authorization?response_type=code&client_id={$appId}&redirect_uri={$redirectUri}";

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

        $response = Http::post('https://api.mercadolibre.com/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $config->app_id,
            'client_secret' => $config->secret_key,
            'code' => $code,
            'redirect_uri' => route('ml.callback'),
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

        return redirect('/admin/companies/mercado-libre-integration')->with('success', 'Conectado com sucesso!');
    }
}
