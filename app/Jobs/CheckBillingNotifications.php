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

        // --- 1. Notificações de Vencimento Próximo ---
        $ordersUpcoming = Order::where('status', 'pending')
            ->whereNotNull('due_date')
            ->whereDate('due_date', $targetDate)
            ->with('user')
            ->get();

        foreach ($ordersUpcoming as $order) {
            $phone = $this->extractPhone($order);
            if (!$phone)
                continue;

            $extraData = ['days' => $daysBefore];

            // WhatsApp
            if (($whatsappConfig['enabled'] ?? false) && $prefs->shouldNotify('days_before_due', 'customer', 'whatsapp')) {
                $msg = $templateService->getFormattedMessage('billing_due_soon_whatsapp', $order, $extraData);
                if ($msg) { // Send only if template exists
                    $whatsapp->sendMessage($whatsappConfig, $phone, $msg);
                    Log::info("WP enviado para {$phone} (Order {$order->id})");

                    // Delay "Anti-Ban" para Evolution API
                    if ($isEvolution) {
                        $sleepSecs = rand(10, 25); // Intervalo variável entre 10s e 25s
                        sleep($sleepSecs);
                    }
                }
            }

            // SMS
            if (($smsConfig['enabled'] ?? false) && $prefs->shouldNotify('days_before_due', 'customer', 'sms')) {
                $msg = $templateService->getFormattedMessage('billing_due_soon_sms', $order, $extraData);
                if ($msg) {
                    $sms->sendSms($smsConfig, $phone, $msg);
                    Log::info("SMS enviado para {$phone} (Order {$order->id})");
                }
            }
        }

        // --- 2. Notificações de Vencidos (Overdue) ---
        $yesterday = now()->subDay()->toDateString();

        $ordersOverdue = Order::where('status', 'pending')
            ->whereNotNull('due_date')
            ->whereDate('due_date', $yesterday)
            ->with('user')
            ->get();

        foreach ($ordersOverdue as $order) {
            $phone = $this->extractPhone($order);
            if (!$phone)
                continue;

            // WhatsApp
            if (($whatsappConfig['enabled'] ?? false) && $prefs->shouldNotify('overdue', 'customer', 'whatsapp')) {
                $msg = $templateService->getFormattedMessage('billing_overdue_whatsapp', $order);
                if ($msg) {
                    $whatsapp->sendMessage($whatsappConfig, $phone, $msg);

                    // Delay "Anti-Ban" para Evolution API
                    if ($isEvolution) {
                        $sleepSecs = rand(10, 25);
                        sleep($sleepSecs);
                    }
                }
            }

            // SMS
            if (($smsConfig['enabled'] ?? false) && $prefs->shouldNotify('overdue', 'customer', 'sms')) {
                $msg = $templateService->getFormattedMessage('billing_overdue_sms', $order);
                if ($msg) {
                    $sms->sendSms($smsConfig, $phone, $msg);
                }
            }
        }
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
