<?php

namespace App\Observers;

use App\Models\License;
use App\Models\User;
use App\Models\Company;
use App\Jobs\SendOnboardingMessageJob;
use Illuminate\Support\Facades\Log;

class LicenseObserver
{
    /**
     * Handle the License "created" event.
     */
    public function created(License $license): void
    {
        $this->notifyLicenseReleased($license);
    }

    /**
     * Handle the License "updated" event.
     */
    public function updated(License $license): void
    {
        Log::info("LicenseObserver: Evento UPDATED disparado para Licença {$license->id}. Dirty: " . json_encode($license->getDirty()));

        // Se a data de expiração mudou para frente E o status é ativo
        // Uso de strtolower para evitar problemas de case (Ativo vs ativo)
        if ($license->isDirty('data_expiracao') && strtolower($license->status) === 'ativo') {

            $newDate = $license->data_expiracao;
            $oldDate = $license->getOriginal('data_expiracao');

            // Verifica se houve extensão de prazo real
            if ($newDate > $oldDate) {
                Log::info("LicenseObserver: Extensão de prazo detectada. Notificando...");
                $this->notifyLicenseReleased($license, true);
            } else {
                Log::info("LicenseObserver: Data não avançou (Nova: {$newDate}, Velha: {$oldDate}). Ignorando.");
            }
        } else {
            Log::info("LicenseObserver: Condições não atendidas. DirtyExp: " . ($license->isDirty('data_expiracao') ? 'S' : 'N') . ", Status: {$license->status}");
        }
    }

    protected function notifyLicenseReleased(License $license, bool $isRenewal = false): void
    {
        Log::info("LicenseObserver: Verificando notificação para licença {$license->id}. IsRenewal: " . ($isRenewal ? 'S' : 'N'));

        // A verificação de origem 'cadastro_trial' foi removida pois impedia notificações
        // de renovação para licenças que nasceram como trial mas viraram pagas.
        // A proteção contra trials curtos já é feita pela verificação de dias (< 15).

        // Se NÃO for renovação explícita, aplica filtros anti-spam (trials, prazos curtos)
        if (!$isRenewal) {
            // A proteção contra trials curtos
            if ($license->data_expiracao && $license->data_expiracao->diffInDays(now()) < 15) {
                Log::info("LicenseObserver: Licença com validade curta (< 15 dias). Ignorando notificação (Provável Trial).");
                return;
            }
        }

        // Encontra o usuário responsável (dono da empresa)
        $company = Company::find($license->empresa_codigo);
        if ($company) {
            $cnpj = $company->cnpj;
            $cnpjLimpo = preg_replace('/\D/', '', $cnpj);

            Log::info("LicenseObserver: Buscando usuário para Empresa: {$company->razao} (CNPJ: {$cnpj})");

            // 1. Tenta por empresa_id (Vínculo Novo - Mais Seguro)
            $user = User::where('empresa_id', $company->codigo)->orderBy('id')->first();

            // 2. Fallback: CNPJ (com ou sem mascara)
            if (!$user) {
                $user = User::where(function ($q) use ($cnpj, $cnpjLimpo) {
                    $q->where('cnpj', $cnpj)->orWhere('cnpj', $cnpjLimpo);
                })->orderBy('id')->first();
            }

            if ($user) {
                Log::info("LicenseObserver: Usuário encontrado: {$user->name} (ID: {$user->id}). Disparando Job.");

                $validity = $license->data_expiracao ? $license->data_expiracao->format('d/m/Y') : 'N/A';

                SendOnboardingMessageJob::dispatch($user, 'license_released', ['validity' => $validity])
                    ->delay(now()->addSeconds(5));
            } else {
                Log::warning("LicenseObserver: Nenhum usuário encontrado para notificar. Company ID: {$company->codigo}, CNPJ Buscado: {$cnpj}");
            }
        } else {
            Log::error("LicenseObserver: Empresa código {$license->empresa_codigo} não encontrada para licença {$license->id}.");
        }
    }
}
