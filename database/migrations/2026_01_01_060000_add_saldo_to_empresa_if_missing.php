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
        if (Schema::hasTable('empresa')) {
            Schema::table('empresa', function (Blueprint $table) {
                if (!Schema::hasColumn('empresa', 'saldo')) {
                    $table->decimal('saldo', 10, 2)->default(0.00)->after('status');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('empresa')) {
            // Não remover para evitar perda de dados
            // if (Schema::hasColumn('empresa', 'saldo')) {
            //    $table->dropColumn('saldo');
            // }
        }
    }
};
