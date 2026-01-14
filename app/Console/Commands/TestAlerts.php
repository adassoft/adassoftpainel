<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CheckBillingNotifications;
use Illuminate\Support\Facades\Log;

class TestAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:alerts {--force : Ignore date checks (not implemented yet)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually trigger the CheckBillingNotifications job to test alerts.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting manual alert check...');

        // --- Diagnostics ---
        $prefs = new \App\Services\NotificationPreferences();
        $daysBefore = $prefs->getDaysBeforeDue();
        $targetDate = now()->addDays($daysBefore)->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $waConfig = (new \App\Services\WhatsappService())->loadConfig();
        $smsConfig = (new \App\Services\SmsService())->loadConfig();

        $this->info("------------------------------------------------");
        $this->info("DIAGNOSTICS:");
        $this->info("Days Before Configured: {$daysBefore}");
        $this->info("Target Date for Warning: {$targetDate}");
        $this->info("Target Date for Overdue: {$yesterday}");
        $this->info("WhatsApp Enabled: " . ($waConfig['enabled'] ? 'YES' : 'NO'));
        $this->info("SMS Enabled: " . ($smsConfig['enabled'] ? 'YES' : 'NO'));

        // Count Matches
        $upcomingCount = \App\Models\License::where('status', 'ativo')
            ->whereDate('data_expiracao', $targetDate)
            ->count();
        $this->info("Active Licenses escaping on {$targetDate}: {$upcomingCount}");

        $expiredCount = \App\Models\License::where('status', 'ativo')
            ->whereDate('data_expiracao', $yesterday)
            ->count();
        $this->info("Active Licenses expired on {$yesterday}: {$expiredCount}");

        // Notifications Enabled?
        $notifyWp = $prefs->shouldNotify('days_before_due', 'customer', 'whatsapp');
        $this->info("Notify Customer via WhatsApp (days_before): " . ($notifyWp ? 'YES' : 'NO'));

        $notifyOverdueWp = $prefs->shouldNotify('overdue', 'customer', 'whatsapp');
        $this->info("Notify Customer via WhatsApp (overdue): " . ($notifyOverdueWp ? 'YES' : 'NO'));

        $notifyEmail = $prefs->shouldNotify('days_before_due', 'customer', 'email');
        $this->info("Notify Customer via Email (days_before): " . ($notifyEmail ? 'YES' : 'NO'));

        $notifyOverdueEmail = $prefs->shouldNotify('overdue', 'customer', 'email');
        $this->info("Notify Customer via Email (overdue): " . ($notifyOverdueEmail ? 'YES' : 'NO'));

        $this->info("------------------------------------------------");

        if ($upcomingCount === 0 && $expiredCount === 0) {
            $this->warn("No licenses match the exact dates. That is why no messages are sent.");
        }

        // Detailed check for Expired licenses
        if ($expiredCount > 0) {
            $this->info("Inspecting {$expiredCount} expired license(s)...");
            $licensesExpired = \App\Models\License::where('status', 'ativo')
                ->whereDate('data_expiracao', $yesterday)
                ->with('company')
                ->get();

            $templateService = new \App\Services\MessageTemplateService();

            foreach ($licensesExpired as $lic) {
                $phone = $lic->company->fone ?? 'N/A';
                $email = $lic->company->email ?? 'N/A';

                $msgWp = $templateService->getFormattedMessage('billing_overdue_whatsapp', $lic);
                $msgEmail = $templateService->getFormattedMessage('billing_overdue_email_body', $lic);

                $this->info(" -> License #{$lic->id} (Company: {$lic->company->razao})");
                $this->info("    Phone: {$phone}");
                $this->info("    Email: {$email}");

                // WhatsApp Checks
                if ($notifyOverdueWp) {
                    if (empty($phone) || $phone === 'N/A')
                        $this->warn("    [WhatsApp] SKIPPED: Phone number missing.");
                    elseif (empty($msgWp))
                        $this->warn("    [WhatsApp] SKIPPED: Template empty.");
                    else
                        $this->info("    [WhatsApp] Ready to send.");
                } else {
                    $this->warn("    [WhatsApp] Disabled in preferences.");
                }

                // Email Checks
                if ($notifyOverdueEmail) {
                    if (empty($email) || $email === 'N/A')
                        $this->warn("    [Email] SKIPPED: Email address missing.");
                    elseif (empty($msgEmail))
                        $this->warn("    [Email] SKIPPED: Template empty.");
                    else
                        $this->info("    [Email] Ready to send.");
                } else {
                    $this->warn("    [Email] Disabled in preferences.");
                }
            }
        }

        try {
            Log::info('Manually starting CheckBillingNotifications via artisan test:alerts');
            $job = new CheckBillingNotifications();
            $job->handle();

            $this->info('Job CheckBillingNotifications finished execution.');
        } catch (\Exception $e) {
            $this->error('Error running job: ' . $e->getMessage());
            Log::error('Error in manual test:alerts: ' . $e->getMessage());
        }
    }
}
