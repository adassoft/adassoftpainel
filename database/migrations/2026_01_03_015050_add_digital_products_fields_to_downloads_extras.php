<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Correção: Adicionar colunas na tabela correta 'downloads_extras'
        Schema::table('downloads_extras', function (Blueprint $table) {
            if (!Schema::hasColumn('downloads_extras', 'preco')) {
                $table->decimal('preco', 10, 2)->nullable()->default(0.00);
            }
            if (!Schema::hasColumn('downloads_extras', 'is_paid')) {
                $table->boolean('is_paid')->default(false);
            }
            if (!Schema::hasColumn('downloads_extras', 'requires_login')) {
                $table->boolean('requires_login')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('downloads_extras', function (Blueprint $table) {
            if (Schema::hasColumn('downloads_extras', 'preco')) {
                $table->dropColumn(['preco', 'is_paid', 'requires_login']);
            }
        });
    }
};
