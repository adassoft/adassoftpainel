<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FixVitaliciaFlags extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:vitalicia-flags';
    protected $description = 'Marca licenças com validade longa (> 2040) como Vitalícias';

    public function handle()
    {
        $this->info("Verificando licenças...");

        $count = \App\Models\License::whereYear('data_expiracao', '>', 2040)
            ->update(['vitalicia' => true]);

        $this->info("Atualizadas $count licenças para Vitalícia.");
    }
}
