<?php

namespace App\Observers;

use App\Models\License;
use App\Models\User;
use App\Models\Company;
use App\Jobs\SendOnboardingMessageJob;
use App\Jobs\SendLicenseNotificationJob;
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
        // Uso de strtolower para evitar problemas de case (Ativo vs ativo)
        if ($license->isDirty('data_expiracao') && strtolower($license->status) === 'ativo') {

            $newDate = $license->data_expiracao;
            $oldDate = $license->getOriginal('data_expiracao');

            // Verifica se houve extensão de prazo real
            if ($newDate > $oldDate) {
                $this->notifyLicenseReleased($license, true);
            }
        }
    }

    protected function notifyLicenseReleased(License $license, bool $isRenewal = false): void
    {
        // A verificação de origem 'cadastro_trial' foi removida pois impedia notificações
        // de renovação para licenças que nasceram como trial mas viraram pagas.
        // A proteção contra trials curtos já é feita pela verificação de dias (< 15).

        // Se NÃO for renovação explícita, aplica filtros anti-spam (trials, prazos curtos)
        if (!$isRenewal) {
            // A proteção contra trials curtos
            if ($license->data_expiracao && $license->data_expiracao->diffInDays(now()) < 15) {
                return;
            }
        }

        // Encontra o usuário responsável (dono da empresa)
        $company = Company::find($license->empresa_codigo);
        if ($company) {
            $cnpj = $company->cnpj;
            $cnpjLimpo = preg_replace('/\D/', '', $cnpj);

            // 1. Tenta por empresa_id (Vínculo Novo - Mais Seguro)
            $user = User::where('empresa_id', $company->codigo)->orderBy('id')->first();

            // 2. Fallback: CNPJ (com ou sem mascara)
            if (!$user) {
                $user = User::where(function ($q) use ($cnpj, $cnpjLimpo) {
                    $q->where('cnpj', $cnpj)->orWhere('cnpj', $cnpjLimpo);
                })->orderBy('id')->first();
            }

            if ($user) {
                $validity = $license->data_expiracao ? $license->data_expiracao->format('d/m/Y') : 'N/A';

                SendLicenseNotificationJob::dispatch($user, [
                    'validity' => $validity,
                    'license_id' => $license->id
                ])
                    ->delay(now()->addSeconds(5));
            } else {
                Log::warning("LicenseObserver: Nenhum usuário encontrado para notificar. Company ID: {$company->codigo}");
            }
        }
    }
}
