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
        SendOnboardingMessageJob::dispatch($user, 'checkin_day1')->delay(now()->addDay()->addHours(9)); // +1 dia, as 9h da manha? 
        // Se eu fizer delay(now()->addDay()), vai ser na mesma hora do cadastro no dia seguinte. OK.
        // Se eu quiser as 9h, complexidade aumenta. Vamos manter +24h simples ou ajustar.
        // O user disse "no dia seguinte".
        // Vamos usar +24h.
        SendOnboardingMessageJob::dispatch($user, 'checkin_day1')->delay(now()->addDay());
    }
}
