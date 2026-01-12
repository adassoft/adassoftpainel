<?php

namespace App\Console\Commands;

use App\Models\License;
use App\Models\SerialHistory;
use Illuminate\Console\Command;

class RepairSerialHistory extends Command
{
    protected $signature = 'shield:repair-history';
    protected $description = 'Repara o histórico de seriais criando registros faltantes para licenças ativas';

    public function handle()
    {
        $this->info('Iniciando reparo do histórico de seriais...');

        $licenses = License::all();
        $count = 0;

        foreach ($licenses as $license) {
            if (empty($license->serial_atual))
                continue;

            $exists = SerialHistory::where('serial_gerado', $license->serial_atual)->exists();

            if (!$exists) {
                SerialHistory::create([
                    'empresa_codigo' => $license->empresa_codigo,
                    'software_id' => $license->software_id,
                    'serial_gerado' => $license->serial_atual,
                    'data_geracao' => $license->data_criacao ?? now(),
                    'validade_licenca' => $license->data_expiracao,
                    'terminais_permitidos' => $license->terminais_permitidos,
                    'ativo' => $license->status === 'ativo',
                    'observacoes' => json_encode(['origem' => 'reparo_automatico']),
                ]);
                $count++;
                $this->info("Criado histórico para serial: {$license->serial_atual}");
            }
        }

        $this->info("Reparo concluído. {$count} registros criados.");
    }
}
