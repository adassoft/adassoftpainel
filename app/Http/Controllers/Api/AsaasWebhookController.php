<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Company;
use App\Models\CreditHistory;
use Illuminate\Support\Facades\Log;

class AsaasWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $data = $request->all();

        // Log básico para debug (storage/logs/laravel.log)
        Log::info('Asaas Webhook Payload:', $data);

        $event = $data['event'] ?? null;
        // Eventos de interesse
        if (!in_array($event, ['PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED', 'PAYMENT_UPDATED'])) {
            return response()->json(['status' => 'ignored']);
        }

        $payment = $data['payment'] ?? [];
        $externalReference = $payment['externalReference'] ?? null;
        $status = $payment['status'] ?? '';

        // Status considerados "Pago"
        $paidStatuses = ['CONFIRMED', 'RECEIVED', 'RECEIVED_IN_CASH', 'RECEIVED_IN_CREDIT_CARD', 'RECEIVED_IN_DEBIT_CARD'];

        if (in_array($status, $paidStatuses)) {

            Log::info("Processando webhook para ref: {$externalReference} | Status: {$status}");

            if (!$externalReference) {
                Log::warning('Webhook Asaas recebido sem externalReference.');
                return response()->json(['status' => 'missing_reference']);
            }

            // Atualizar Pedido
            $order = Order::where('cod_transacao', $externalReference)->first();

            if ($order) {
                Log::info("Pedido encontrado: ID {$order->id} | Recorrencia: {$order->recorrencia}");

                $order->situacao = 'pago';
                $order->data_pagamento = now();
                $order->save();

                Log::info("Pedido {$externalReference} atualizado para PAGO.");

                // Lógica de Crédito
                $isCredito = ($order->recorrencia === 'CREDITO');

                if ($isCredito) {
                    Log::info("Iniciando lógica de crédito para CNPJ: {$order->cnpj}");
                    $company = Company::where('cnpj', $order->cnpj)->first();

                    if ($company) {
                        $company->increment('saldo', $order->valor);

                        CreditHistory::create([
                            'empresa_cnpj' => $order->cnpj,
                            'tipo' => 'entrada',
                            'valor' => $order->valor,
                            'descricao' => 'Recarga Automática via PIX/Asaas',
                            'data_movimento' => now()
                        ]);

                        Log::info("Crédito de R$ {$order->valor} adicionado para CNPJ {$order->cnpj}. Saldo Atual: {$company->saldo}");
                    } else {
                        Log::error("Empresa não encontrada para CNPJ {$order->cnpj} ao processar crédito.");
                    }
                } else {
                    Log::info("Pedido não é de crédito. Recorrencia: " . $order->recorrencia);
                }
            } else {
                Log::warning("Pedido não encontrado para ref: $externalReference no banco de dados.");
            }
        }

        return response()->json(['status' => 'success']);
    }
}
