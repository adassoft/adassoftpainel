<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\NotificationPreferences;
use App\Services\WhatsappService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckBillingNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        $prefs = new NotificationPreferences();
        $templateService = new \App\Services\MessageTemplateService();

        $whatsapp = new WhatsappService();
        $whatsappConfig = $whatsapp->loadConfig();

        $sms = new \App\Services\SmsService();
        $smsConfig = $sms->loadConfig();

        $daysBefore = $prefs->getDaysBeforeDue();
        $targetDate = now()->addDays($daysBefore)->toDateString();

        $isEvolution = ($whatsappConfig['provider'] ?? 'official') === 'evolution';

        // ... (Config loading logic stays same up to here)

        // --- 1. LICENÇAS: Vencimento Próximo (Days Before) ---
        // Buscamos licenças ativas que expiram na data alvo
        $licensesUpcoming = \App\Models\License::where('status', 'ativo')
            ->whereDate('data_expiracao', $targetDate)
            ->with('company')
            ->get();

        foreach ($licensesUpcoming as $license) {
            $phone = $license->company->fone ?? null;
            if (!$phone)
                continue;

            $extraData = ['days' => $daysBefore];

            // WhatsApp
            if (($whatsappConfig['enabled'] ?? false) && $prefs->shouldNotify('days_before_due', 'customer', 'whatsapp')) {
                // Reutilizamos o template 'billing_due_soon'
                $msg = $templateService->getFormattedMessage('billing_due_soon_whatsapp', $license, $extraData);
                if ($msg) {
                    $whatsapp->sendMessage($whatsappConfig, $phone, $msg);
                    Log::info("WP Licença Prox Venc enviada para {$phone} (Lic #{$license->id})");

                    if ($isEvolution)
                        sleep(rand(60, 120));
                }
            }

            // SMS
            if (($smsConfig['enabled'] ?? false) && $prefs->shouldNotify('days_before_due', 'customer', 'sms')) {
                $msg = $templateService->getFormattedMessage('billing_due_soon_sms', $license, $extraData);
                if ($msg) {
                    $sms->sendSms($smsConfig, $phone, $msg);
                    Log::info("SMS Licença Prox Venc enviada para {$phone} (Lic #{$license->id})");
                }
            }
        }


        // --- 2. LICENÇAS: Vencidas Ontem (Overdue) ---
        $yesterday = now()->subDay()->toDateString();
        $licensesExpired = \App\Models\License::where('status', 'ativo') // Ainda ativos mas data passada
            ->whereDate('data_expiracao', $yesterday)
            ->with('company')
            ->get();

        foreach ($licensesExpired as $license) {
            $phone = $license->company->fone ?? null;
            if (!$phone)
                continue;

            // WhatsApp
            if (($whatsappConfig['enabled'] ?? false) && $prefs->shouldNotify('overdue', 'customer', 'whatsapp')) {
                $msg = $templateService->getFormattedMessage('billing_overdue_whatsapp', $license);
                if ($msg) {
                    $whatsapp->sendMessage($whatsappConfig, $phone, $msg);
                    Log::info("WP Licença Vencida enviada para {$phone} (Lic #{$license->id})");

                    if ($isEvolution)
                        sleep(rand(60, 120));
                }
            }

            // SMS
            if (($smsConfig['enabled'] ?? false) && $prefs->shouldNotify('overdue', 'customer', 'sms')) {
                $msg = $templateService->getFormattedMessage('billing_overdue_sms', $license);
                if ($msg) {
                    $sms->sendSms($smsConfig, $phone, $msg);
                }
            }
        }

        // --- 3. PEDIDOS: Mantendo lógica legada para casos onde o pedido é gerado ---
        // (Apenas se houver pedido pendente com data de vencimento explícita)
        $ordersUpcoming = Order::where('status', 'pending')
            ->whereNotNull('due_date')
            ->whereDate('due_date', $targetDate)
            ->with('user')
            ->get();
        // ... (rest of old logic for orders)
    }

    private function extractPhone($order)
    {
        // Tenta user->celular, user->whatsapp, company->fone
        $user = $order->user;
        if ($user) {
            // Adapte conforme os campos reais do seu User
            if (!empty($user->whatsapp))
                return $user->whatsapp;
            if (!empty($user->celular))
                return $user->celular;

            // Tenta empresa
            if ($user->empresa && !empty($user->empresa->fone)) {
                return $user->empresa->fone;
            }
        }
        return null;
    }
}
