<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tenta adicionar a coluna via SQL direto, ignorando erro se já existir (Duplicate column name)
        try {
            DB::statement("ALTER TABLE revenda_config ADD COLUMN is_default TINYINT(1) NOT NULL DEFAULT 0 AFTER ativo");
        } catch (\Exception $e) {
            // Se der erro 1060 (Duplicate column name), ignora. Se for outro, lança.
            if (!str_contains($e->getMessage(), 'Duplicate column name')) {
                // Tenta verificar se é erro de tabela não encontrada ou outro
                // Se não for duplicate column, pode ser outra coisa, mas vamos assumir que se falhar, ou já existe ou a tabela tá ruim.
                // Vamos tentar pelo método seguro do Laravel como fallback
                if (!Schema::hasColumn('revenda_config', 'is_default')) {
                    Schema::table('revenda_config', function (Blueprint $table) {
                        $table->boolean('is_default')->default(false)->after('ativo');
                    });
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('revenda_config', 'is_default')) {
            Schema::table('revenda_config', function (Blueprint $table) {
                $table->dropColumn('is_default');
            });
        }
    }
};
