<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendResellerInsufficientBalanceJob implements ShouldQueue
{
    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Queue\SerializesModels, \Illuminate\Bus\Queueable;

    protected $reseller;
    protected $requiredAmount;
    protected $order;
    protected $attempt;

    /**
     * Create a new job instance.
     */
    public function __construct(\App\Models\Company $reseller, float $requiredAmount, \App\Models\Order $order, int $attempt = 1)
    {
        $this->reseller = $reseller;
        $this->requiredAmount = $requiredAmount;
        $this->order = $order;
        $this->attempt = $attempt;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Reload reseller fresh data
        $reseller = $this->reseller->fresh();

        \Illuminate\Support\Facades\Log::info("Job Insuficiencia Saldo: Iniciando para Revenda {$reseller->razao}. Tentativa {$this->attempt}. Saldo: {$reseller->saldo}");

        // Se o pedido já for ativado (licença gerada), para a insistência
        if ($this->order->fresh()->licenca_id) {
            \Illuminate\Support\Facades\Log::info("Job Insuficiencia Saldo: Pedido {$this->order->id} já possui licença. Abortando.");
            return;
        }

        // Verifica saldo
        if ($reseller->saldo < $this->requiredAmount) {
            // == AINDA SEM SALDO ==

            // 1. Enviar Notificação (WhatsApp / Email)
            $this->sendWarning($reseller);

            // 2. Re-agendar para daqui a 1 hora (Insistência)
            // Limite de tentativas para não spammar eternamente (ex: 24h)
            if ($this->attempt <= 24) {
                \Illuminate\Support\Facades\Log::info("Job Insuficiencia Saldo: Reagendando para 1h.");
                self::dispatch($reseller, $this->requiredAmount, $this->order, $this->attempt + 1)
                    ->delay(now()->addHour());
            }
        } else {
            // == SALDO SUFICIENTE AGORA ==
            // Opcional: Notificar que agora tem saldo e pedir pra liberar?
            // Ou tentar reprocessar?
            // Por segurança, vamos mandar mensagem: "Saldo detectado! Acesse o painel para liberar a licença pendente."
            $this->sendSuccessHint($reseller);
        }
    }

    protected function sendWarning($reseller)
    {
        $phone = $reseller->fone;
        $msg = "⚠️ *AdasSoft Alerta Revenda*\n\n";
        $msg .= "O cliente final (Pedido #{$this->order->id}) realizou o pagamento, mas sua carteira está *SEM SALDO* para liberar a licença.\n\n";
        $msg .= "Valor Necessário: R$ " . number_format($this->requiredAmount, 2, ',', '.') . "\n";
        $msg .= "Saldo Atual: R$ " . number_format($reseller->saldo, 2, ',', '.') . "\n\n";
        $msg .= "Por favor, *faça uma recarga imediatamente* para não deixar seu cliente esperando.";

        // ZAP
        if ($phone) {
            try {
                $wa = new \App\Services\WhatsappService();
                $wa->sendMessage($wa->loadConfig(), $phone, $msg);
                \Illuminate\Support\Facades\Log::info("Job Insuficiencia Saldo: Mensagem enviada para {$phone}");
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Job Insuficiencia Saldo: Erro ao enviar Zap: " . $e->getMessage());
            }
        } else {
            \Illuminate\Support\Facades\Log::warning("Job Insuficiencia Saldo: Revenda sem telefone cadastrado.");
        }
    }

    protected function sendSuccessHint($reseller)
    {
        $phone = $reseller->fone;
        $msg = "✅ *AdasSoft Info*\n\n";
        $msg .= "Detectamos saldo suficiente na sua carteira!\n";
        $msg .= "Acesse o painel agora para liberar a licença do Pedido #{$this->order->id} pendente.";

        if ($phone) {
            try {
                $wa = new \App\Services\WhatsappService();
                $wa->sendMessage($wa->loadConfig(), $phone, $msg);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Job Insuficiencia Saldo (Success): Erro ao enviar Zap: " . $e->getMessage());
            }
        }
    }
}
