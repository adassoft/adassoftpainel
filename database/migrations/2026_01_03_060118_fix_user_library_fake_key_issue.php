<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Tentativa força bruta de corrigir a FK
        try {
            // Tenta dropar a FK antiga (se existir)
            DB::statement('ALTER TABLE user_library DROP FOREIGN KEY user_library_user_id_foreign');
        } catch (\Exception $e) {
            // Ignora erro se não existir
        }

        try {
            // Tenta adicionar a FK correta
            // Nota: O nome da constraint precisa ser unico/padrao
            Schema::table('user_library', function (Blueprint $table) {
                $table->foreign('user_id', 'user_id_fk_fix') // Nome explicito diferente para evitar conflito
                    ->references('id')
                    ->on('usuario')
                    ->cascadeOnDelete();
            });
        } catch (\Exception $e) {
            // Se falhar, loga ou ignora se já existir
        }
    }

    public function down(): void
    {
        // Irreversivel de forma segura automatica
    }
};
