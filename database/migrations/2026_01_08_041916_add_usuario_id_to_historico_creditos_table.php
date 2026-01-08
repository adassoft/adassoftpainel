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
        Schema::table('historico_creditos', function (Blueprint $table) {
            if (!Schema::hasColumn('historico_creditos', 'usuario_id')) {
                $table->integer('usuario_id')->after('empresa_cnpj')->nullable()->comment('ID do usuário que fez o lançamento');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historico_creditos', function (Blueprint $table) {
            if (Schema::hasColumn('historico_creditos', 'usuario_id')) {
                $table->dropColumn('usuario_id');
            }
        });
    }
};
