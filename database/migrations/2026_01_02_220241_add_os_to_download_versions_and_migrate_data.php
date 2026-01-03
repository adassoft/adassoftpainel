<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add Column
        if (!Schema::hasColumn('download_versions', 'sistema_operacional')) {
            Schema::table('download_versions', function (Blueprint $table) {
                $table->string('sistema_operacional')->default('windows')->after('versao'); // windows, linux, mac, android, ios, any
            });
        }

        // 2. Migrate Existing Data (Move Parent Data to Versions Table if not exists)
        // This ensures the current "Main" version is listed in history
        $downloads = \Illuminate\Support\Facades\DB::table('downloads_extras')->get();
        foreach ($downloads as $d) {
            // Check if this version already exists in history
            $exists = \Illuminate\Support\Facades\DB::table('download_versions')
                ->where('download_id', $d->id)
                ->where('versao', $d->versao)
                ->exists();

            if (!$exists && !empty($d->arquivo_path)) {
                \Illuminate\Support\Facades\DB::table('download_versions')->insert([
                    'download_id' => $d->id,
                    'versao' => $d->versao ?: '1.0.0',
                    'sistema_operacional' => 'windows', // Default assumption
                    'arquivo_path' => $d->arquivo_path,
                    'tamanho' => $d->tamanho,
                    'contador' => $d->contador,
                    'changelog' => 'Versão inicial (Importada)',
                    'data_lancamento' => $d->data_atualizacao ?: now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('download_versions', 'sistema_operacional')) {
            Schema::table('download_versions', function (Blueprint $table) {
                $table->dropColumn('sistema_operacional');
            });
        }
    }
};
