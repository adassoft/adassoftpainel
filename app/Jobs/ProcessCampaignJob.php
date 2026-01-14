<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

        // Intervalo entre mensagens (Randomizado 60s a 120s)
        // Lógica: Mantemos um acumulador de tempo. Cada mensagem é agendada para 
        // (agora + acumulador). O acumulador cresce de forma aleatória a cada passo.

        $accumulatedDelay = 0; // Primeira mensagem sai imediatamente (ou quase)

        foreach ($licenses as $license) {

            $delayDate = now()->addSeconds($accumulatedDelay);

            \App\Jobs\SendCampaignMessageJob::dispatch($this->campaign, $license)
                ->delay($delayDate);

            // Sorteia o tempo para o PRÓXIMO envio (entre 1 e 2 minutos)
            $randomInterval = rand(60, 120);
            $accumulatedDelay += $randomInterval;
        }
    }
}
