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
        Schema::table('noticias', function (Blueprint $table) {
            DB::statement("ALTER TABLE noticias MODIFY COLUMN publico ENUM('revenda', 'todos', 'cliente') DEFAULT 'todos'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('noticias', function (Blueprint $table) {
            // Nota: Se houver dados 'cliente', isso pode falhar ou converter para '' no strict mode.
            DB::statement("ALTER TABLE noticias MODIFY COLUMN publico ENUM('revenda', 'todos') DEFAULT 'todos'");
        });
    }
};
