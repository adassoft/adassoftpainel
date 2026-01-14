<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessCampaignJob implements ShouldQueue
{
    use Queueable;

    protected $campaign;

    /**
     * Create a new job instance.
     */
    public function __construct(\App\Models\MessageCampaign $campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->campaign->update(['status' => 'processing']);

        $query = \App\Models\License::query();

        // Aplica Filtros
        if ($this->campaign->target_software_id) {
            $query->where('software_id', $this->campaign->target_software_id);
        }

        if ($this->campaign->target_license_status !== 'all') {
            $query->where('status', $this->campaign->target_license_status);
        }

        // Eager Load Company
        $licenses = $query->with('company')->get();

        $total = $licenses->count();
        $this->campaign->update(['total_targets' => $total]);

        if ($total === 0) {
            $this->campaign->update(['status' => 'completed']);
            return;
        }

        // Intervalo entre mensagens (em segundos)
        // O usuário pediu 1 a 2 minutos. Vamos colocar 90 segundos (1.5 min) como média segura.
        $intervalSeconds = 90;

        foreach ($licenses as $index => $license) {
            $delay = now()->addSeconds($index * $intervalSeconds);

            \App\Jobs\SendCampaignMessageJob::dispatch($this->campaign, $license)
                ->delay($delay);
        }
    }
}
