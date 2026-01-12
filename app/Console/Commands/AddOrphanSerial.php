<?php

namespace App\Console\Commands;

use App\Models\SerialHistory;
use App\Models\License;
use App\Models\Company;
use App\Models\Software;
use Illuminate\Console\Command;

class AddOrphanSerial extends Command
{
    protected $signature = 'shield:add-serial {serial} {empresa_id} {software_id}';
    protected $description = 'Adiciona manualmente um serial órfão ao histórico e licença ativa';

    public function handle()
    {
        $serial = trim($this->argument('serial'));
        $empresaId = (int) $this->argument('empresa_id');
        $softwareId = (int) $this->argument('software_id');

        $empresa = Company::where('codigo', $empresaId)->first();
        if (!$empresa) {
            $this->error("Empresa ID $empresaId não encontrada.");
            return;
        }

        $software = Software::find($softwareId);
        if (!$software) {
            $this->error("Software ID $softwareId não encontrado.");
            return;
        }

        $this->info("Adicionando serial: $serial para {$empresa->razao}");

        // 1. Adicionar ao Histórico
        SerialHistory::firstOrCreate(
            ['serial_gerado' => $serial],
            [
                'empresa_codigo' => $empresaId,
                'software_id' => $softwareId,
                'data_geracao' => now(),
                'validade_licenca' => now()->addYear(),
                'terminais_permitidos' => 1,
                'ativo' => true,
                'observacoes' => json_encode(['origem' => 'manual_add_serial']),
            ]
        );

        // 2. Atualizar Licença Ativa
        License::updateOrCreate(
            ['empresa_codigo' => $empresaId, 'software_id' => $softwareId],
            [
                'serial_atual' => $serial,
                'status' => 'ativo',
                'terminais_permitidos' => 1,
                'data_expiracao' => now()->addYear(),
                'observacoes' => json_encode(['origem' => 'manual_add_serial'])
            ]
        );

        $this->info("Serial adicionado e vinculado com sucesso!");
    }
}
