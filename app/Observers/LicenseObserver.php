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
        // Se a data de expiração mudou para frente E o status é ativo
        if ($license->isDirty('data_expiracao') && $license->status === 'Ativo') {

            $newDate = $license->data_expiracao;
            $oldDate = $license->getOriginal('data_expiracao');

            // Verifica se houve extensão de prazo real
            if ($newDate > $oldDate) {
                $this->notifyLicenseReleased($license);
            }
        }
    }

    protected function notifyLicenseReleased(License $license): void
    {
        Log::info("LicenseObserver: Verificando notificação para licença {$license->id}...");

        // Verifica origem nas observações
        $obs = json_decode($license->observacoes ?? '{}', true);
        if (($obs['origem'] ?? '') === 'cadastro_trial') {
            return;
        }

        // Ou verifica se dias < 15
        if ($license->data_expiracao && $license->data_expiracao->diffInDays(now()) < 15) {
            return;
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
                SendOnboardingMessageJob::dispatch($user, 'license_released')->delay(now()->addSeconds(5));
            } else {
                Log::warning("LicenseObserver: Nenhum usuário encontrado para notificar. Company ID: {$company->codigo}, CNPJ Buscado: {$cnpj}");
            }
        } else {
            Log::error("LicenseObserver: Empresa código {$license->empresa_codigo} não encontrada para licença {$license->id}.");
        }
    }
}
