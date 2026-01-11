<?php

namespace App\Observers;

use App\Models\License;
use App\Models\User;
use App\Models\Company;
use App\Jobs\SendOnboardingMessageJob;

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
        // Verifica se é licença de cliente válida (não Trial curto de 7 dias criado no cadastro)
        // Se for trial, NÃO manda "Licença Liberada" (já mandou boas vindas).
        // Como diferenciar? 
        // Trial criado no cadastro tem obs 'cadastro_trial' ou dias < 30.
        // Se a validade for pequena (<= 10 dias), assume trial e silencia?
        // User query: "quando usuário comprar a licença...".
        // Então só manda se foi COMPRADO.
        // O LicenseObserver não sabe se foi comprado.
        // Mas o trial é criado no começo.
        // Se for Renovação, manda.
        // Se for Criação:
        //   - Se for Manual/Webhook (Paga): Manda.
        //   - Se for Automática Trial: Não Manda.

        // Verifica origem nas observações
        $obs = json_decode($license->observacoes ?? '{}', true);
        if (($obs['origem'] ?? '') === 'cadastro_trial') {
            return;
        }

        // Ou verifica se dias < 15
        if ($license->data_expiracao && $license->data_expiracao->diffInDays(now()) < 15) {
            // Provavelmente trial ou teste curto.
            return;
        }

        // Encontra o usuário responsável (dono da empresa)
        $company = Company::find($license->empresa_codigo);
        if ($company) {
            // Busca Master ou primeiro usuário da empresa
            $user = User::where('cnpj', $company->cnpj)->orderBy('id')->first();

            // Fallback: busca por empresa_id (novo padrão)
            if (!$user) {
                $user = User::where('empresa_id', $company->codigo)->orderBy('id')->first();
            }

            if ($user) {
                SendOnboardingMessageJob::dispatch($user, 'license_released')->delay(now()->addSeconds(5));
            }
        }
    }
}
