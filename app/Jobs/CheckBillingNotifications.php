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
        $whatsapp = new WhatsappService();
        $config = $whatsapp->loadConfig(); // Load base WhatsApp config

        if (!($config['enabled'] ?? false)) {
            Log::info('CheckBillingNotifications: WhatsApp disabled.');
            return;
        }

        // 1. Notificar Vencimento Próximo (Days Before)
        $daysBefore = $prefs->getDaysBeforeDue();
        $targetDate = now()->addDays($daysBefore)->toDateString();

        // Se a config de notificar cliente (WhatsApp) estiver ativa
        if ($prefs->shouldNotify('days_before_due', 'customer', 'whatsapp')) {
            $orders = Order::where('status', 'pending')
                ->whereNotNull('due_date')
                ->whereDate('due_date', $targetDate)
                ->with('user')
                ->get();

            foreach ($orders as $order) {
                if ($order->user && $order->user->email) { // Use email as unique id check or phone
                    // Tenta achar telefone (assumindo que user tem 'fone' ou 'celular' ou empresa)
                    $phone = $this->extractPhone($order);

                    if ($phone) {
                        $msg = "Olá {$order->user->nome}, sua fatura Adassoft vence em {$daysBefore} dias. Valor: R$ {$order->total}. Link: {$order->payment_url}";
                        $whatsapp->sendMessage($config, $phone, $msg);
                        Log::info("Notificacão enviada para {$phone} (Order {$order->id})");
                    }
                }
            }
        }

        // 2. Notificar Vencidos (Overdue) - Ex: 1 dia de atraso
        if ($prefs->shouldNotify('overdue', 'customer', 'whatsapp')) {
            $yesterday = now()->subDay()->toDateString();

            $orders = Order::where('status', 'pending')
                ->whereNotNull('due_date')
                ->whereDate('due_date', $yesterday)
                ->with('user')
                ->get();

            foreach ($orders as $order) {
                $phone = $this->extractPhone($order);
                if ($phone) {
                    $msg = "URGENTE: Sua fatura Adassoft venceu ontem via {$order->payment_url}. Regularize para evitar bloqueio.";
                    $whatsapp->sendMessage($config, $phone, $msg);
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
