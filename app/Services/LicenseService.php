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
}
