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
        Log::info('Manually starting CheckBillingNotifications via artisan test:alerts');

        try {
            $job = new CheckBillingNotifications();
            $job->handle();

            $this->info('Job CheckBillingNotifications finished successfully.');
            $this->info('Check the "Logs de Mensagens" in the admin panel to see if any messages were sent.');
        } catch (\Exception $e) {
            $this->error('Error running job: ' . $e->getMessage());
            Log::error('Error in manual test:alerts: ' . $e->getMessage());
        }
    }
}
