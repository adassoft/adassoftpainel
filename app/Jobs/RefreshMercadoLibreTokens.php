<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\Models\MercadoLibreConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RefreshMercadoLibreTokens implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Busca tokens que vão expirar nas próximas 2 horas ou já expiraram
        $configs = MercadoLibreConfig::where('is_active', true)
            ->where('expires_at', '<=', now()->addHours(2))
            ->get();

        Log::info("Iniciando refresh de tokens ML. Encontrados: " . $configs->count());

        foreach ($configs as $config) {
            try {
                $response = Http::post('https://api.mercadolibre.com/oauth/token', [
                    'grant_type' => 'refresh_token',
                    'client_id' => $config->app_id,
                    'client_secret' => $config->secret_key,
                    'refresh_token' => $config->refresh_token,
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    $config->update([
                        'access_token' => $data['access_token'],
                        'refresh_token' => $data['refresh_token'], // As vezes muda, as vezes não, melhor salvar
                        'expires_at' => now()->addSeconds($data['expires_in']),
                    ]);

                    Log::info("Token renovado com sucesso para Company ID: {$config->company_id} / User ML: {$config->ml_user_id}");
                } else {
                    Log::error("Falha ao renovar token ML ID {$config->id}: " . $response->body());

                    // Se for erro de autenticação (ex: refresh token inválido/revogado), pode desativar
                    if ($response->status() === 400 || $response->status() === 401) {
                        $config->update(['is_active' => false]);
                        Log::warning("Integração ML ID {$config->id} desativada por falha no refresh.");
                    }
                }
            } catch (\Exception $e) {
                Log::error("Exceção ao renovar token ML ID {$config->id}: " . $e->getMessage());
            }
        }
    }
}
