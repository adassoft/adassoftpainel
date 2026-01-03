<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Software;
use App\Models\License;
use App\Models\SerialHistory;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LicenseService
{
    protected string $secret;
    protected string $offlineSecret;

    public function __construct()
    {
        $this->secret = config('shield.license_secret', 'defina-um-segredo-seguro-e-unico');
        $this->offlineSecret = config('shield.offline_secret', 'defina-um-segredo-offline');
    }

    public function generateSerial(Company $company, Software $software): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $software->nome_software), 0, 3));
        if (strlen($prefix) < 3) {
            $prefix = str_pad($prefix, 3, 'X');
        }

        $cnpjClean = preg_replace('/[^0-9]/', '', $company->cnpj);

        for ($i = 0; $i < 5; $i++) {
            $entropy = implode(':', [
                $cnpjClean,
                $software->id,
                microtime(true),
                bin2hex(random_bytes(8))
            ]);

            $hash = strtoupper(bin2hex(hash_hmac('sha256', $entropy, $this->secret, true)));
            $hash = substr($hash, 0, 12);
            $segmented = trim(chunk_split($hash, 4, '-'), '-');
            $serial = sprintf('SER-%s-%s', $prefix, $segmented);

            if (!SerialHistory::where('serial_gerado', $serial)->exists()) {
                return $serial;
            }
        }

        throw new Exception('Não foi possível gerar um serial único.');
    }

    public function createLicense(Company $company, Software $software, int $validityDays = 30, int $terminals = 1)
    {
        return DB::transaction(function () use ($company, $software, $validityDays, $terminals) {
            // Calculate validity with rollover
            $rolloverDays = 0;
            $currentLicense = License::where('empresa_codigo', $company->codigo)
                ->where('software_id', $software->id)
                ->first();

            if ($currentLicense && in_array($currentLicense->status, ['ativo', 'suspenso'])) {
                $expiration = $currentLicense->data_expiracao;
                if ($expiration && \Carbon\Carbon::parse($expiration)->isFuture()) {
                    $rolloverDays = now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($expiration)->startOfDay());
                }
            }

            $totalDays = $validityDays + $rolloverDays;
            $expiryDate = now()->addDays($totalDays)->format('Y-m-d');

            $serial = $this->generateSerial($company, $software);

            // Inactivate old serials
            SerialHistory::where('empresa_codigo', $company->codigo)
                ->where('software_id', $software->id)
                ->where('ativo', true)
                ->get()
                ->each(function ($oldSerial) {
                    $obs = json_decode($oldSerial->observacoes, true) ?: [];
                    $obs['status_inativacao'] = 'renovado';
                    $obs['status_inativacao_data'] = now()->toIso8601String();
                    $oldSerial->update([
                        'ativo' => false,
                        'observacoes' => json_encode($obs)
                    ]);
                });

            $payload = [
                'serial' => $serial,
                'empresa_codigo' => $company->codigo,
                'software_id' => $software->id,
                'validade' => $expiryDate,
                'terminais' => $terminals,
                'emitido_em' => now()->toIso8601String()
            ];

            if ($rolloverDays > 0) {
                $payload['saldo_remanescente_dias'] = $rolloverDays;
                $payload['validade_solicitada_dias'] = $validityDays;
                $payload['validade_total_dias'] = $totalDays;
            }

            $token = $this->generateToken($payload);

            $obsJson = json_encode([
                'modo' => 'online',
                'token' => $token,
                'payload' => $payload,
                'saldo_remanescente_dias' => $rolloverDays,
                'validade_solicitada_dias' => $validityDays,
                'validade_total_dias' => $totalDays
            ]);

            // Create History
            SerialHistory::create([
                'empresa_codigo' => $company->codigo,
                'software_id' => $software->id,
                'serial_gerado' => $serial,
                'data_geracao' => now(),
                'validade_licenca' => $expiryDate,
                'terminais_permitidos' => $terminals,
                'observacoes' => $obsJson,
                'ativo' => true,
            ]);

            // Update/Create Active License
            $license = License::updateOrCreate(
                ['empresa_codigo' => $company->codigo, 'software_id' => $software->id],
                [
                    'serial_atual' => $serial,
                    'data_ativacao' => now(),
                    'data_expiracao' => $expiryDate,
                    'terminais_permitidos' => $terminals,
                    'status' => 'ativo',
                    'observacoes' => $obsJson
                ]
            );

            return [
                'license' => $license,
                'serial' => $serial,
                'token' => $token,
                'expiry_date' => $expiryDate
            ];
        });
    }

    public function generateToken(array $payload): string
    {
        ksort($payload);
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $encoded = $this->base64url_encode($json);
        $signature = hash_hmac('sha256', $encoded, $this->secret);
        return $encoded . '.' . $signature;
    }

    protected function base64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    // === Métodos Adicionais para Suporte ao ValidationController ===

    public function generateLicenseToken(array $payload): string
    {
        return $this->generateToken($payload);
    }

    public function validateLicenseToken(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2)
            return ['valido' => false, 'erro' => 'Formato de token inválido'];

        [$encoded, $signature] = $parts;
        $expectedSignature = hash_hmac('sha256', $encoded, $this->secret);

        if (!hash_equals($expectedSignature, $signature)) {
            return ['valido' => false, 'erro' => 'Assinatura inválida'];
        }

        $json = base64_decode(strtr($encoded, '-_', '+/'));
        $payload = json_decode($json, true);

        if (!$payload)
            return ['valido' => false, 'erro' => 'Payload corrompido'];

        if (isset($payload['expira_em']) && \Carbon\Carbon::parse($payload['expira_em'])->isPast()) {
            return ['valido' => false, 'erro' => 'Token expirado', 'payload' => $payload];
        }

        return ['valido' => true, 'payload' => $payload];
    }

    public function validateSerialFull(string $serial): array
    {
        // 1. Busca Histórico
        $history = SerialHistory::where('serial_gerado', $serial)->first();
        if (!$history) {
            return ['valido' => false, 'erro' => 'Serial não encontrado'];
        }

        if (!$history->ativo) {
            return ['valido' => false, 'erro' => 'Serial inativado/substituído'];
        }

        // 2. Busca Licença Ativa
        $license = License::where('serial_atual', $serial)->first();
        if (!$license) {
            return ['valido' => false, 'erro' => 'Licença não vinculada a este serial'];
        }

        if ($license->status !== 'ativo') {
            return ['valido' => false, 'erro' => 'Licença suspensa ou cancelada'];
        }

        if ($license->data_expiracao && \Carbon\Carbon::parse($license->data_expiracao)->isPast()) {
            return [
                'valido' => false,
                'erro' => 'Licença expirada',
                'data_inicio' => ($license->data_ultima_renovacao ?? $license->data_ativacao ?? $license->data_criacao)?->format('Y-m-d'),
                'data_expiracao' => $license->data_expiracao->format('Y-m-d')
            ];
        }

        return [
            'valido' => true,
            'licenca_id' => $license->id,
            'empresa_codigo' => $license->empresa_codigo,
            'data_inicio' => ($license->data_ultima_renovacao ?? $license->data_ativacao ?? $license->data_criacao)?->format('Y-m-d'),
            'data_expiracao' => $license->data_expiracao ? $license->data_expiracao->format('Y-m-d') : null,
            'terminais_permitidos' => $license->terminais_permitidos,
            'terminais_utilizados' => $license->terminais_utilizados
        ];
    }

    public function registerTerminalUsage(string $serial, string $mac, string $computerName, string $installId, string $ip): array
    {
        $license = License::where('serial_atual', $serial)->first();
        if (!$license)
            return ['success' => false, 'erro' => 'Licença não encontrada'];

        // Verifica se terminal já existe
        $terminal = \App\Models\Terminal::firstOrCreate(
            ['MAC' => $mac],
            ['NOME_COMPUTADOR' => $computerName]
        );

        // Verifica vínculo
        $vinculo = \App\Models\TerminalSoftware::where('licenca_id', $license->id)
            ->where('terminal_codigo', $terminal->CODIGO)
            ->first();

        if ($vinculo && $vinculo->ativo) {
            // Já registrado, atualiza heartbeat
            $vinculo->update(['ultima_atividade' => now(), 'ip_origem' => $ip]);
            return ['success' => true, 'msg' => 'Terminal já registrado'];
        }

        // Se não registrado, verifica limite
        if ($license->terminais_utilizados >= $license->terminais_permitidos) {
            // Tenta ver se algum terminal inativo pode liberar vaga (opcional)
            // Por enquanto bloqueia
            return ['success' => false, 'erro' => 'Limite de terminais atingido'];
        }

        // Registra novo vínculo
        if ($vinculo) {
            $vinculo->update(['ativo' => 1, 'ultima_atividade' => now(), 'ip_origem' => $ip]);
        } else {
            \App\Models\TerminalSoftware::create([
                'licenca_id' => $license->id,
                'terminal_codigo' => $terminal->CODIGO,
                'ativo' => 1,
                'data_vinculo' => now(),
                'ultima_atividade' => now(),
                'ip_origem' => $ip,
                'instalacao_id' => $installId
            ]);
        }

        $license->increment('terminais_utilizados');

        return ['success' => true, 'msg' => 'Terminal registrado com sucesso'];
    }
}
