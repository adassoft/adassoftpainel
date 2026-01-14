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
        $licensesUpcoming = \App\Models\License::where('status', 'ativo')
            ->whereDate('data_expiracao', $targetDate)
            ->with('company')
            ->get();

        foreach ($licensesUpcoming as $license) {
            $phone = $license->company->fone ?? null;
            $email = $license->company->email ?? null;

            $extraData = ['days' => $daysBefore];

            // 1. WhatsApp
            if ($phone && ($whatsappConfig['enabled'] ?? false) && $prefs->shouldNotify('days_before_due', 'customer', 'whatsapp')) {
                $msg = $templateService->getFormattedMessage('billing_due_soon_whatsapp', $license, $extraData);
                if ($msg) {
                    $whatsapp->sendMessage($whatsappConfig, $phone, $msg);
                    Log::info("WP Licença Prox Venc enviada para {$phone} (Lic #{$license->id})");
                    if ($isEvolution)
                        sleep(rand(60, 120));
                }
            }

            // 2. SMS
            if ($phone && ($smsConfig['enabled'] ?? false) && $prefs->shouldNotify('days_before_due', 'customer', 'sms')) {
                $msg = $templateService->getFormattedMessage('billing_due_soon_sms', $license, $extraData);
                if ($msg)
                    $sms->sendSms($smsConfig, $phone, $msg);
            }

            // 3. Email
            if ($email && $prefs->shouldNotify('days_before_due', 'customer', 'email')) {
                $subject = $templateService->getFormattedMessage('billing_due_soon_email_subject', $license, $extraData);
                $body = $templateService->getFormattedMessage('billing_due_soon_email_body', $license, $extraData);

                if ($subject && $body) {
                    $this->sendEmail($email, $subject, $body);
                    Log::info("Email Licença Prox Venc enviada para {$email} (Lic #{$license->id})");
                }
            }
        }


        // --- 2. LICENÇAS: Vencidas Ontem (Overdue) ---
        $yesterday = now()->subDay()->toDateString();
        $licensesExpired = \App\Models\License::where('status', 'ativo')
            ->whereDate('data_expiracao', $yesterday)
            ->with('company')
            ->get();

        foreach ($licensesExpired as $license) {
            $phone = $license->company->fone ?? null;
            $email = $license->company->email ?? null;

            // 1. WhatsApp
            if ($phone && ($whatsappConfig['enabled'] ?? false) && $prefs->shouldNotify('overdue', 'customer', 'whatsapp')) {
                $msg = $templateService->getFormattedMessage('billing_overdue_whatsapp', $license);
                if ($msg) {
                    $whatsapp->sendMessage($whatsappConfig, $phone, $msg);
                    Log::info("WP Licença Vencida enviada para {$phone} (Lic #{$license->id})");
                    if ($isEvolution)
                        sleep(rand(60, 120));
                }
            }

            // 2. SMS
            if ($phone && ($smsConfig['enabled'] ?? false) && $prefs->shouldNotify('overdue', 'customer', 'sms')) {
                $msg = $templateService->getFormattedMessage('billing_overdue_sms', $license);
                if ($msg)
                    $sms->sendSms($smsConfig, $phone, $msg);
            }

            // 3. Email
            if ($email && $prefs->shouldNotify('overdue', 'customer', 'email')) {
                $subject = $templateService->getFormattedMessage('billing_overdue_email_subject', $license);
                $body = $templateService->getFormattedMessage('billing_overdue_email_body', $license);

                if ($subject && $body) {
                    $this->sendEmail($email, $subject, $body);
                    Log::info("Email Licença Vencida enviada para {$email} (Lic #{$license->id})");
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

    private function sendEmail(string $to, string $subject, string $body)
    {
        try {
            // Retrieve Mail Config
            $config = \App\Models\Configuration::where('chave', 'email_config')->first();
            $data = $config ? json_decode($config->valor, true) : [];

            if (empty($data['host']) || empty($data['username'])) {
                Log::warning("Skipping Email to {$to}: SMTP not configured.");
                return;
            }

            // Using Symfony Mailer manually to respect dynamic config (as used in ManageEmail)
            // Or simpler: config(['mail.mailers.smtp...' => ...]) but that's global.
            // Let's use the same logic as ManageEmail test for reliability.

            $transport = (new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
                $data['host'],
                $data['port'],
                ($data['secure'] ?? '') === 'ssl'
            ))
                ->setUsername($data['username'])
                ->setPassword($data['password']);

            $mailer = new \Symfony\Component\Mailer\Mailer($transport);

            $email = (new \Symfony\Component\Mime\Email())
                ->from(new \Symfony\Component\Mime\Address($data['from_email'], $data['from_name'] ?? 'Adassoft'))
                ->to($to)
                ->subject($subject)
                ->text($body); // Plain text for now as template is text

            $mailer->send($email);

            // Log is handled by listener? 
            // The listener listens to Laravel's MessageSent event. 
            // Symfony Mailer direct usage might NOT fire Laravel events.
            // So we must log manually here to ensure it appears in MessageLog.

            \App\Models\MessageLog::create([
                'channel' => 'email',
                'recipient' => $to,
                'subject' => $subject,
                'body' => $body,
                'status' => 'sent',
                'sent_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send email to {$to}: " . $e->getMessage());

            \App\Models\MessageLog::create([
                'channel' => 'email',
                'recipient' => $to,
                'subject' => $subject,
                'body' => $body,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'sent_at' => now(),
            ]);
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
