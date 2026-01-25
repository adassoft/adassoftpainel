<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckScheduledCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:check';
    protected $description = 'Check for scheduled campaigns and dispatch them';

    public function handle()
    {
        $campaigns = \App\Models\MessageCampaign::where('status', 'draft')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($campaigns as $campaign) {
            $this->info("Dispatching campaign: {$campaign->title}");

            $campaign->update(['status' => 'pending']);
            \App\Jobs\ProcessCampaignJob::dispatch($campaign);
        }

        $this->info("Checked " . $campaigns->count() . " campaigns.");
    }
}
