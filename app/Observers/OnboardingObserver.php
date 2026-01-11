<?php

namespace App\Observers;

use App\Models\User;
use App\Jobs\SendOnboardingMessageJob;

class OnboardingObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Regra: Apenas para Clientes (acesso = 3)
        // Evita disparar para Admin/Revenda ou usuários importados?
        // Se created via ImportLegacyUsers, 'App::runningInConsole()' pode ser checked.
        // Mas se quisermos que importados recebam? Não, importados são velhos.
        // ImportLegacyUsers roda via console. Podemos bloquear se runningInConsole?
        // O user disse "assim que o usuário se cadastrar".

        // Verifica se acesso é cliente (3)
        if ((int) $user->acesso !== 3) {
            return;
        }

        // Verifica se não estamos rodando via CLI (Importações em lote)
        if (app()->runningInConsole()) {
            return;
        }

        // 1. Mensagem de Boas-vindas (Imediato, mas via queue para nao travar API)
        SendOnboardingMessageJob::dispatch($user, 'welcome')->delay(now()->addSeconds(10));

        // 2. Check-in Dia seguinte (24h depois)
        SendOnboardingMessageJob::dispatch($user, 'checkin_day1')->delay(now()->addDay());

        // 3. Dicas (3 dias depois)
        SendOnboardingMessageJob::dispatch($user, 'tips_day3')->delay(now()->addDays(3));

        // 4. Fechamento (6 dias depois - Véspera do fim de 7 dias)
        SendOnboardingMessageJob::dispatch($user, 'closing_day6')->delay(now()->addDays(6));
    }
}
