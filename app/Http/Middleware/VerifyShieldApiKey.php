<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyShieldApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Aceita header X-API-KEY ou query param api_key
        $key = $request->header('X-API-KEY') ?? $request->input('api_key');

        if (!$key) {
            return response()->json([
                'success' => false,
                'error' => 'API Key não fornecida.',
                'code' => 'MISSING_API_KEY'
            ], 401);
        }

        // Hash SHA-256 para comparação
        $hash = hash('sha256', $key);

        $apiKeyRecord = ApiKey::where('key_hash', $hash)
            ->where('status', 'ativo')
            ->first();

        if (!$apiKeyRecord) {
            return response()->json([
                'success' => false,
                'error' => 'API Key inválida ou inativa.',
                'code' => 'INVALID_API_KEY'
            ], 401);
        }

        // Validação de Expiração
        if ($apiKeyRecord->expires_at && $apiKeyRecord->expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'error' => 'API Key expirada.',
                'code' => 'EXPIRED_API_KEY'
            ], 401);
        }

        // Validação de Vínculo com Software (CRÍTICO)
        $reqSoftwareId = $request->input('software_id');
        if ($apiKeyRecord->software_id && $reqSoftwareId) {
            if ((int) $apiKeyRecord->software_id !== (int) $reqSoftwareId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Esta API Key não pertence ao software informado.',
                    'code' => 'SOFTWARE_MISMATCH'
                ], 403);
            }
        }

        // Verifica se o Software em si está ativo (se houver software vinculado)
        if ($apiKeyRecord->software) {
            // 1. Status do Software
            if (strtolower($apiKeyRecord->software->status ?? '') === 'inativo') {
                return response()->json([
                    'success' => false,
                    'error' => 'O software vinculado a esta chave foi descontinuado ou está inativo.',
                    'code' => 'SOFTWARE_INACTIVE'
                ], 403);
            }

            // 2. Validação RIGOROSA de Versão (REMOVIDO: Para permitir updates de patch/minor sem trocar Key)
            /*
            $reqVersao = $request->input('versao_software');
            // Apenas valida se o client enviou a versão (para não quebrar chamadas antigas sem versão, se houver)
            if ($reqVersao && !empty($apiKeyRecord->software->versao)) {
                // Normaliza para comparação (trim)
                $vCadastro = trim($apiKeyRecord->software->versao);
                $vClient = trim($reqVersao);

                // Compara Strings (Ex: '4.0' != '5.0')
                if ($vCadastro !== $vClient) {
                    return response()->json([
                        'success' => false,
                        'error' => "Versão incompatível. A chave utilizada pertence à versão {$vCadastro}, mas o software é {$vClient}. Atualize sua chave ou software.",
                        'code' => 'VERSION_MISMATCH',
                        'expected' => $vCadastro,
                        'received' => $vClient
                    ], 403);
                }
            }
            */
        }

        // Atualizar métricas de uso (opcional, pode ser async)
        $apiKeyRecord->increment('use_count');
        $apiKeyRecord->update(['last_used_at' => now()]);

        // Injetar contexto da chave no request para uso nos controllers
        $request->merge(['_api_key_scopes' => $apiKeyRecord->scopes]);
        $request->attributes->set('api_key_id', $apiKeyRecord->id);

        // --- Proteção contra Replay Attack ---
        $clientTimestamp = $request->input('timestamp');

        // Em ambiente de Dev/Teste, se o cliente ainda não mandou, podemos ser lenientes ou exigir logo.
        // Vamos exigir para garantir segurança.
        if (!$clientTimestamp) {
            return response()->json([
                'success' => false,
                'error' => 'Requisicao inválida (Timestamp ausente). Atualize seu software.',
                'code' => 'MISSING_TIMESTAMP'
            ], 400); // Bad Request
        }

        try {
            $clientTime = \Carbon\Carbon::parse($clientTimestamp);
            $serverTime = now();

            // Tolerância de 10 minutos (5 pra frente, 5 pra tras) para evitar problemas com relogios desincronizados
            if ($clientTime->diffInMinutes($serverTime) > 10) {
                return response()->json([
                    'success' => false,
                    'error' => 'Relogio do sistema desincronizado. Verifique a data/hora do seu computador.',
                    'server_time' => $serverTime->toIso8601String(),
                    'code' => 'CLOCK_DRIFT'
                ], 401);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Formato de data inválido.',
                'code' => 'INVALID_TIMESTAMP'
            ], 400);
        }

        return $next($request);
    }
}
