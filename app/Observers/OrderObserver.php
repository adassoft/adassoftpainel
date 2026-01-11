<?php

namespace App\Observers;

use App\Models\Order;
use App\Jobs\SendOnboardingMessageJob;

class OrderObserver
{
    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Verifica se o status mudou para PAGO
        if ($order->isDirty('status') || $order->isDirty('situacao')) {
            $isPaid = false;

            // Check status (Asaas pattern)
            if (in_array(strtolower($order->status), ['paid', 'confirmed', 'received'])) {
                $isPaid = true;
            }

            // Check situacao (Legacy pattern)
            if (in_array(strtolower($order->situacao), ['pago', 'aprovado'])) {
                $isPaid = true;
            }

            // Apenas se for a primeira vez que fica pago (evita duplo disparo se atualizar meta-dados depois)
            // Se o valor ORIGINAL não era pago
            $wasPaid = false;
            $originalStatus = $order->getOriginal('status');
            $originalSituacao = $order->getOriginal('situacao');

            if (in_array(strtolower($originalStatus), ['paid', 'confirmed', 'received']))
                $wasPaid = true;
            if (in_array(strtolower($originalSituacao), ['pago', 'aprovado']))
                $wasPaid = true;

            if ($isPaid && !$wasPaid) {
                // Dispara job pós-compra (15 dias)
                if ($order->user) {
                    SendOnboardingMessageJob::dispatch($order->user, 'post_purchase_15d')->delay(now()->addDays(15));
                }
            }
        }
    }
}
