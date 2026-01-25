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
            Log::warning("Reseller Webhook: Pedido não encontrado. PaymentID: $paymentId Ref: $externalRef");
            return response()->json(['status' => 'order_not_found'], 200);
        }

        if ($order->status === 'paid') {
            Log::info("Reseller Webhook: Pedido #{$order->id} já estava pago. Ignorando.");
            return response()->json(['status' => 'already_paid']);
        }

        // Atualizar
        $order->update([
            'status' => 'paid',
            'updated_at' => now(),
            // Campos legados
            'situacao' => 'pago',
            'data_pagamento' => now()
        ]);

        Log::info("Reseller Webhook: Pedido #{$order->id} (User: {$order->user_id}) atualizado para PAGO.");

        // 1. Liberar Produtos Digitais (Se houver itens)
        if ($order->items()->count() > 0) {
            foreach ($order->items as $item) {
                // Double check para garantir que download_id existe (pode ter sido nullOnDelete)
                if ($item->download_id) {
                    \App\Models\UserLibrary::firstOrCreate([
                        'user_id' => $order->user_id,
                        'download_id' => $item->download_id
                    ], [
                        'order_id' => $order->id
                    ]);
                }
            }
            Log::info("Reseller Webhook: Produtos digitais liberados para usuário {$order->user_id}");
        } else {
            Log::warning("Reseller Webhook: Pedido PAGO #{$order->id} não tem itens de produtos digitais.");
        }

        // 2. Lógica de Ativação de Licença (Consome Saldo da Revenda)
        if ($order->plano_id) {
            try {
                $plano = \App\Models\Plano::find($order->plano_id);

                if ($plano) {
                    $custoLicenca = $plano->valor; // Custo base da plataforma para a revenda

                    // Busca Revenda pelo CNPJ salvo no pedido
                    $cnpjRevenda = $order->cnpj_revenda;
                    $cnpjLimpo = preg_replace('/\D/', '', $cnpjRevenda);

                    $revenda = \App\Models\Company::where(function ($q) use ($cnpjRevenda, $cnpjLimpo) {
                        $q->where('cnpj', $cnpjRevenda)->orWhere('cnpj', $cnpjLimpo);
                    })->first();

                    if ($revenda) {
                        // Verifica se a revenda tem saldo suficiente
                        if ($revenda->saldo >= $custoLicenca) {
                            // === TEM SALDO: DESCONTA E ATIVA ===
                            $revenda->decrement('saldo', $custoLicenca);

                            // Log Débito no Histórico
                            \App\Models\CreditHistory::create([
                                'empresa_cnpj' => preg_replace('/\D/', '', $revenda->cnpj), // Sempre limpo
                                'tipo' => 'saida',
                                'valor' => $custoLicenca,
                                'descricao' => "Licença Pedido #{$order->id} ({$plano->nome_plano})",
                                'data_movimento' => now()
                            ]);

                            Log::info("Saldo debitado da revenda {$revenda->razao}: -R$ {$custoLicenca}. Novo Saldo: {$revenda->saldo}");

                            // --- ATIVAÇÃO DE LICENÇA ---
                            // Busca dados do CLIENTE FINAL (Empresa que vai usar o software)
                            $userCliente = \App\Models\User::find($order->user_id);
                            if ($userCliente) {
                                // Assegura busca da empresa do cliente
                                $cnpjCliente = $userCliente->cnpj;
                                $cnpjClienteLimpo = preg_replace('/\D/', '', $cnpjCliente);
                                $empresaCliente = \App\Models\Company::where(function ($q) use ($cnpjCliente, $cnpjClienteLimpo) {
                                    $q->where('cnpj', $cnpjCliente)->orWhere('cnpj', $cnpjClienteLimpo);
                                })->first();

                                if ($empresaCliente) {
                                    $validadeDias = 30; // Padrão
                                    if (strtoupper($plano->recorrencia) === 'ANUAL')
                                        $validadeDias = 365;
                                    if (strtoupper($plano->recorrencia) === 'TRIMESTRAL')
                                        $validadeDias = 90;
                                    if (strtoupper($plano->recorrencia) === 'SEMESTRAL')
                                        $validadeDias = 180;

                                    if ($order->licenca_id) {
                                        // Renovação
                                        $license = \App\Models\License::find($order->licenca_id);
                                        if ($license) {
                                            $baseDate = ($license->data_expiracao > now()) ? $license->data_expiracao : now();
                                            $novaData = \Carbon\Carbon::parse($baseDate)->addDays($validadeDias);
                                            $license->update([
                                                'data_expiracao' => $novaData,
                                                'data_ultima_renovacao' => now(),
                                                'status' => 'Ativo'
                                            ]);
                                            Log::info("Licença renovada (Revenda) ID #{$license->id}.");
                                        }
                                    } else {
                                        // Nova Licença
                                        // Evita duplicidade
                                        $existe = \App\Models\License::where('empresa_codigo', $empresaCliente->codigo)
                                            ->where('software_id', $plano->software_id)
                                            ->exists();

                                        if (!$existe) {
                                            \App\Models\License::create([
                                                'empresa_codigo' => $empresaCliente->codigo,
                                                'cnpj_revenda' => $revenda->cnpj, // Vincula à revenda que pagou
                                                'software_id' => $plano->software_id,
                                                'serial_atual' => strtoupper(\Illuminate\Support\Str::random(20)),
                                                'data_criacao' => now(),
                                                'data_ativacao' => now(),
                                                'data_expiracao' => now()->addDays($validadeDias),
                                                'data_ultima_renovacao' => now(),
                                                'terminais_permitidos' => 1,
                                                'status' => 'Ativo'
                                            ]);
                                            Log::info("Nova licença criada (Revenda) para cliente {$empresaCliente->razao}.");
                                        }
                                    }
                                } else {
                                    Log::error("Empresa do cliente final não encontrada (CNPJ: {$userCliente->cnpj})");
                                }
                            }

                        } else {
                            // === SEM SALDO ===
                            Log::warning("FALHA ATIVAÇÃO: Revenda {$revenda->razao} (CNPJ {$revenda->cnpj}) SEM SALDO. Necessário: {$custoLicenca}, Disponível: {$revenda->saldo}");

                            // Dispara Job de Insistência (Cobrança)
                            \App\Jobs\SendResellerInsufficientBalanceJob::dispatch($revenda, $custoLicenca, $order);

                            // Marca pedido como pendente de saldo
                            $order->update(['observacoes' => 'Pendente: Saldo insuficiente na revenda.']);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("Erro Webhook Revenda (Licença): " . $e->getMessage());
            }
        }

        return response()->json(['status' => 'success']);
    }
}
