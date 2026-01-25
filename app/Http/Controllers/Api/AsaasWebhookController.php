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
        // Log::info('Asaas Webhook Payload:', $data);

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

            // Log::info("Processando webhook para ref: {$externalReference} | Status: {$status}");

            if (!$externalReference) {
                Log::warning('Webhook Asaas recebido sem externalReference.');
                return response()->json(['status' => 'missing_reference']);
            }

            // Busca Flexível: Tenta encontrar o pedido por várias referências
            $order = Order::where('external_reference', $externalReference)
                ->orWhere('external_id', $payment['id'] ?? 'N/A')
                ->orWhere('asaas_payment_id', $payment['id'] ?? 'N/A')
                ->first();

            if ($order) {
                // Log::info("Pedido encontrado: ID {$order->id}");

                // Atualiza Status Universal e Campos Legados
                $order->update([
                    'status' => 'paid',
                    'situacao' => 'pago',
                    'data_pagamento' => now()
                ]);

                Log::info("Pedido {$order->id} ({$externalReference}) atualizado para PAGO.");

                // 1. Lógica de Produtos Digitais (Reativada para Vendas Diretas da Plataforma)
                try {
                    if ($order->items()->count() > 0) {
                        foreach ($order->items as $item) {
                            if ($item->download_id) {
                                \App\Models\UserLibrary::firstOrCreate([
                                    'user_id' => $order->user_id,
                                    'download_id' => $item->download_id
                                ], [
                                    'order_id' => $order->id
                                ]);
                            }
                        }
                        Log::info("Webhook Plataforma: Produtos digitais liberados para usuário {$order->user_id}");
                    }
                } catch (\Exception $e) {
                    Log::error("Erro no Webhook Asaas (Produtos Digitais): " . $e->getMessage());
                    Log::error($e->getTraceAsString());
                    // Não dar throw para não travar o webhook, mas precisamos saber o erro.
                    // Se falhar aqui, o pedido fica pago mas o produto não é entregue.
                }

                // Lógica de Créditos e Planos (Plataforma -> Revenda)
                $recorrencia = $order->recorrencia ?? ''; // Null coalescing para evitar erro
                $cnpj = $order->cnpj ?? $order->cnpj_revenda; // Tenta pegar CNPJ alvo
                $valor = $order->valor ?? $order->total;

                $isCredito = ($recorrencia === 'CREDITO');

                if ($isCredito) {
                    $cnpjLimpo = preg_replace('/\D/', '', $cnpj);
                    Log::info("Iniciando lógica de crédito para CNPJ: {$cnpj} (Limpo: {$cnpjLimpo})");

                    // Tenta buscar pelo CNPJ original ou pelo limpo
                    $company = Company::where('cnpj', $cnpj)->first();

                    if (!$company && $cnpj !== $cnpjLimpo) {
                        $company = Company::where('cnpj', $cnpjLimpo)->first();
                    }

                    if ($company) {
                        $company->increment('saldo', $valor);

                        // Garante que o histórico fique vinculado ao CNPJ limpo se a empresa usa limpo
                        $cnpjHistorico = preg_replace('/\D/', '', $company->cnpj);

                        CreditHistory::create([
                            'empresa_cnpj' => $cnpjHistorico,
                            'tipo' => 'entrada',
                            'valor' => $valor,
                            'descricao' => 'Recarga Automática via PIX/Asaas',
                            'data_movimento' => now()
                        ]);

                        Log::info("Crédito de R$ {$valor} adicionado para CNPJ {$company->cnpj}. Saldo Atual: {$company->saldo}");
                    } else {
                        Log::error("Empresa não encontrada para CNPJ {$cnpj} (nem limpo {$cnpjLimpo}) ao processar crédito.");
                    }
                } else {
                    // Apenas loga se não for produto digital, para evitar log duplicado confuso
                    if ($order->items()->count() == 0) {
                        Log::info("Pedido processado como Assinatura/Plano (Recorrencia: {$recorrencia})");
                    }
                }
                // --- Lógica de Assinaturas / Licenças (Novo) ---
                // Se for pedido de REVENDA, ignoramos aqui pois o ResellerWebhookController deve tratar (com validação de saldo)
                if ($order->plano_id && !$order->cnpj_revenda) {
                    try {
                        Log::info("Processando Licença para Pedido Venda Direta {$order->id} (Plano: {$order->plano_id})");
                        $plano = \App\Models\Plano::find($order->plano_id);

                        // Busca o usuário dono do pedido
                        $user = \App\Models\User::find($order->user_id);

                        // Busca a empresa pelo CNPJ do usuário (limpo ou original)
                        $cnpjUser = $user->cnpj;
                        $cnpjLimpo = preg_replace('/\D/', '', $cnpjUser);

                        $empresa = \App\Models\Company::where(function ($q) use ($cnpjUser, $cnpjLimpo) {
                            $q->where('cnpj', $cnpjUser)->orWhere('cnpj', $cnpjLimpo);
                        })->first();

                        if ($plano && $empresa) {
                            $validadeDias = 30; // Padrão Mensal
                            if (strtoupper($plano->recorrencia) === 'ANUAL')
                                $validadeDias = 365;
                            if (strtoupper($plano->recorrencia) === 'TRIMESTRAL')
                                $validadeDias = 90;
                            if (strtoupper($plano->recorrencia) === 'SEMESTRAL')
                                $validadeDias = 180;

                            if ($order->licenca_id) {
                                // === RENOVAÇÃO ===
                                Log::info("Renovando Licença ID: {$order->licenca_id}");
                                $license = \App\Models\License::find($order->licenca_id);
                                if ($license) {
                                    // Se a data atual for maior que a expiração, renova a partir de hoje.
                                    // Se ainda não venceu, soma dias na data futura.
                                    $dataRef = $license->data_expiracao > now() ? $license->data_expiracao : now();
                                    $novaData = \Carbon\Carbon::parse($dataRef)->addDays($validadeDias);

                                    $license->update([
                                        'data_expiracao' => $novaData,
                                        'data_ultima_renovacao' => now(),
                                        'status' => 'Ativo'
                                    ]);
                                    Log::info("Licença #{$license->id} renovada com sucesso até {$novaData->format('d/m/Y')}.");
                                } else {
                                    Log::error("Licença ID {$order->licenca_id} não encontrada para renovação.");
                                }
                            } else {
                                // === NOVA LICENÇA ===
                                Log::info("Criando Nova Licença para Empresa: {$empresa->razao}");

                                // Verifica se já existe licença para este software e empresa
                                // Pega a mais recente (ordena por expiração decrescente)
                                $existingLicense = \App\Models\License::where('empresa_codigo', $empresa->codigo)
                                    ->where('software_id', $plano->software_id)
                                    ->orderBy('data_expiracao', 'desc')
                                    ->first();

                                if ($existingLicense) {
                                    // === RENOVAÇÃO AUTOMÁTICA DE LICENÇA EXISTENTE ===
                                    Log::info("Licença existente encontrada (ID {$existingLicense->id}). Processando renovação.");

                                    $dataRef = $existingLicense->data_expiracao > now() ? $existingLicense->data_expiracao : now();
                                    $novaData = \Carbon\Carbon::parse($dataRef)->addDays($validadeDias);

                                    $existingLicense->update([
                                        'data_expiracao' => $novaData,
                                        'data_ultima_renovacao' => now(),
                                        'status' => 'ativo' // Ensure case compatibility with other checks
                                    ]);

                                    // Vincular o pedido à licença existente para futuro
                                    $order->update(['licenca_id' => $existingLicense->id]);

                                    Log::info("Licença existente #{$existingLicense->id} renovada até {$novaData->format('d/m/Y')}.");
                                } else {
                                    // === CRIAÇÃO DE NOVA LICENÇA ===
                                    $newLicense = \App\Models\License::create([
                                        'empresa_codigo' => $empresa->codigo,
                                        'cnpj_revenda' => $order->cnpj_revenda,
                                        'software_id' => $plano->software_id,
                                        'serial_atual' => strtoupper(\Illuminate\Support\Str::random(20)),
                                        'data_criacao' => now(),
                                        'data_ativacao' => now(),
                                        'data_expiracao' => now()->addDays($validadeDias),
                                        'data_ultima_renovacao' => now(),
                                        'terminais_permitidos' => 1,
                                        'status' => 'ativo'
                                    ]);

                                    // Atualiza pedido
                                    $order->update(['licenca_id' => $newLicense->id]);

                                    Log::info("Nova licença criada com sucesso. ID: {$newLicense->id}");
                                }
                            }
                        } else {
                            Log::error("Plano ou Empresa não encontrados para processar licença. Plano: " . ($plano ? 'OK' : 'NULL') . ", Empresa: " . ($empresa ? 'OK' : 'NULL'));
                        }
                    } catch (\Exception $e) {
                        Log::error("Erro Crítico no Webhook (Licenças): " . $e->getMessage());
                        // Não relança erro para não travar o webhook
                    }
                }

            } else {
                Log::warning("Pedido não encontrado para ref: $externalReference no banco de dados.");
            }
        }

        return response()->json(['status' => 'success']);
    }
}
