<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class ResellerWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();

        $event = $payload['event'] ?? null;
        $payment = $payload['payment'] ?? [];

        Log::info('Webhook Asaas recebido', ['event' => $event, 'payment_id' => $payment['id'] ?? 'N/A']);

        if ($event === 'PAYMENT_RECEIVED' || $event === 'PAYMENT_CONFIRMED') {
            return $this->processPaymentReceived($payment);
        }

        return response()->json(['status' => 'ignored_event']);
    }

    protected function processPaymentReceived(array $payment)
    {
        $paymentId = $payment['id'] ?? null;
        $externalRef = $payment['externalReference'] ?? null;

        if (!$paymentId)
            return response()->json(['error' => 'invalid_payload'], 400);

        // Buscar Pedido
        $order = Order::where('asaas_payment_id', $paymentId)->first();

        // Fallback para externalRef
        if (!$order && $externalRef) {
            $order = Order::where('external_reference', $externalRef)->first();
        }

        if (!$order) {
            Log::warning("Webhook Asaas: Pedido não encontrado. PaymentID: $paymentId Ref: $externalRef");
            // Retornamos 200 pro Asaas parar de mandar se não acharmos, ou 404 se quisermos que retente.
            // Geralmente 200 com log de erro é mais seguro para não engarrafar fila.
            return response()->json(['status' => 'order_not_found'], 200);
        }

        if ($order->status === 'paid') {
            return response()->json(['status' => 'already_paid']);
        }

        // Atualizar Status
        // Atualizar Status
        $order->update([
            'status' => 'paid',
            'situacao' => 'pago', // Compatibilidade
            'paid_at' => now(),
            'data_pagamento' => now(),
            'updated_at' => now()
        ]);

        Log::info("Pedido #{$order->id} (User: {$order->user_id}) marcado como PAGO via Webhook (Reseller).");

        // 1. Liberar Produtos Digitais (Se houver itens)
        if ($order->items()->count() > 0) {
            foreach ($order->items as $item) {
                \App\Models\UserLibrary::firstOrCreate([
                    'user_id' => $order->user_id,
                    'download_id' => $item->download_id
                ], [
                    'order_id' => $order->id
                ]);
            }
            Log::info("Produtos digitais liberados na biblioteca do usuário {$order->user_id}");
        }

        // TODO: Disparar Evento ou Job para ativação de planos (se necessário)
        // event(new \App\Events\OrderPaid($order));

        return response()->json(['status' => 'success']);
    }
}
