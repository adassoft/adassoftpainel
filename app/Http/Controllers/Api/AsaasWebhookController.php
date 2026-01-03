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

            // Busca Flexível: Tenta encontrar o pedido por várias referências
            $order = Order::where('external_reference', $externalReference)
                ->orWhere('external_id', $payment['id'] ?? 'N/A')
                ->orWhere('asaas_payment_id', $payment['id'] ?? 'N/A')
                ->orWhere('cod_transacao', $externalReference) // Legacy support
                ->first();

            if ($order) {
                Log::info("Pedido encontrado: ID {$order->id}");

                // Atualiza Status Universal
                $order->status = 'paid';
                $order->paid_at = now();

                // Campos Legados (se existirem no objeto via magic set, ou se mapeados)
                $order->situacao = 'pago';
                $order->data_pagamento = now();

                $order->save();

                Log::info("Pedido {$order->id} ({$externalReference}) atualizado para PAGO.");

                // 1. Lógica de Produtos Digitais (Prioridade se tiver itens)
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
                // 2. Lógica Legada (Planos e Créditos)
                else {
                    $recorrencia = $order->recorrencia ?? ''; // Null coalescing para evitar erro
                    $cnpj = $order->cnpj ?? $order->cnpj_revenda; // Tenta pegar CNPJ alvo
                    $valor = $order->valor ?? $order->total;

                    $isCredito = ($recorrencia === 'CREDITO');

                    if ($isCredito) {
                        Log::info("Iniciando lógica de crédito para CNPJ: {$cnpj}");
                        $company = Company::where('cnpj', $cnpj)->first();

                        if ($company) {
                            $company->increment('saldo', $valor);

                            CreditHistory::create([
                                'empresa_cnpj' => $cnpj,
                                'tipo' => 'entrada',
                                'valor' => $valor,
                                'descricao' => 'Recarga Automática via PIX/Asaas',
                                'data_movimento' => now()
                            ]);

                            Log::info("Crédito de R$ {$valor} adicionado para CNPJ {$cnpj}. Saldo Atual: {$company->saldo}");
                        } else {
                            Log::error("Empresa não encontrada para CNPJ {$cnpj} ao processar crédito.");
                        }
                    } else {
                        Log::info("Pedido processado como Assinatura/Plano (Recorrencia: {$recorrencia})");
                        // Aqui poderia entrar lógica de ativar licença se não for automático
                    }
                }
            } else {
                Log::warning("Pedido não encontrado para ref: $externalReference no banco de dados.");
            }
        }

        return response()->json(['status' => 'success']);
    }
}
