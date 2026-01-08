<?php

namespace App\Console\Commands;

use App\Models\License;
use Illuminate\Console\Command;

class RecalculateLicenses extends Command
{
    protected $signature = 'shield:recalc-licenses';
    protected $description = 'Recalcula o uso de terminais para todas as licenças ativas';

    public function handle()
    {
        $this->info('Iniciando recálculo das licenças...');

        $licencas = License::all();
        $bar = $this->output->createProgressBar(count($licencas));

        foreach ($licencas as $license) {
            $license->recalculateUsage();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Recálculo concluído com sucesso!');
    }
}
