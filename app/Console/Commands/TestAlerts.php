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

        $this->info("------------------------------------------------");
        $this->info("DIAGNOSTICS:");
        $this->info("Days Before Configured: {$daysBefore}");
        $this->info("Target Date for Warning: {$targetDate}");
        $this->info("Target Date for Overdue: {$yesterday}");

        // Check Services Status
        $waConfig = (new \App\Services\WhatsappService())->loadConfig();
        $this->info("WhatsApp Enabled: " . ($waConfig['enabled'] ? 'YES' : 'NO'));

        $smsConfig = (new \App\Services\SmsService())->loadConfig();
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

        $this->info("------------------------------------------------");

        if ($upcomingCount === 0 && $expiredCount === 0) {
            $this->warn("No licenses match the exact dates. That is why no messages are sent.");
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
