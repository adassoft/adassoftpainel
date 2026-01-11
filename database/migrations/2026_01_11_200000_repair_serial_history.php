<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\License;
use App\Models\SerialHistory;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Script de Reparo Automático de Histórico de Seriais
        // Executa na migração para garantir que rode em produção no deploy

        $licenses = License::all();
        foreach ($licenses as $license) {
            if (empty($license->serial_atual))
                continue;

            // Verifica se existe no histórico
            $exists = SerialHistory::where('serial_gerado', $license->serial_atual)->exists();

            if (!$exists) {
                SerialHistory::create([
                    'empresa_codigo' => $license->empresa_codigo,
                    'software_id' => $license->software_id,
                    'serial_gerado' => $license->serial_atual,
                    'data_geracao' => $license->data_criacao ?? now(),
                    'validade_licenca' => $license->data_expiracao,
                    'terminais_permitidos' => $license->terminais_permitidos ?? 1,
                    'ativo' => in_array($license->status, ['ativo', 'suspenso']),
                    'observacoes' => json_encode(['origem' => 'migration_repair_history']),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Não reverte reparos de dados
    }
};
