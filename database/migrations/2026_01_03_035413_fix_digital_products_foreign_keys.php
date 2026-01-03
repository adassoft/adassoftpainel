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
        // 1. Corrigir FK em 'order_items'
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['download_id']);
            $table->foreign('download_id')
                ->references('id')
                ->on('downloads_extras')
                ->nullOnDelete();
        });

        // 2. Corrigir FK em 'user_library'
        Schema::table('user_library', function (Blueprint $table) {
            $table->dropForeign(['download_id']);
            $table->foreign('download_id')
                ->references('id')
                ->on('downloads_extras')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['download_id']);
            // Restaurar para anterior (incorreto, mas histórico)
            $table->foreign('download_id')
                ->references('id')
                ->on('downloads')
                ->nullOnDelete();
        });

        Schema::table('user_library', function (Blueprint $table) {
            $table->dropForeign(['download_id']);
            $table->foreign('download_id')
                ->references('id')
                ->on('downloads')
                ->cascadeOnDelete();
        });
    }
};
