<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\License;

class MarkTrialLicenses extends Command
{
    protected $signature = 'license:mark-trials';
    protected $description = 'Marca licenças antigas como trial com base na heurística de duração e renovação.';

    public function handle()
    {
        $this->info('Iniciando varredura de licenças Trial...');

        // Lógica: Nunca renovou E duração < 30 dias (ex: 29 dias ou menos)
        // E (opcional) criado recentemente ou antiguidades?
        // Se quisermos marcar TODO O PASSADO, não usamos filtro de data_criacao.

        $count = 0;

        License::whereNull('data_ultima_renovacao')
            ->where('is_trial', false) // Só quem ainda não é trial
            ->whereRaw('DATEDIFF(data_expiracao, data_criacao) < 30')
            ->chunkById(500, function ($licenses) use (&$count) {
                foreach ($licenses as $license) {
                    $license->update(['is_trial' => true]);
                    $count++;
                }
                $this->info("Processadas 500 licenças...");
            });

        $this->info("Concluído! {$count} licenças marcadas como Trial.");
    }
}
