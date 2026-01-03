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
        if (!Schema::hasTable('download_versions')) {
            Schema::create('download_versions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('download_id')->index();
                $table->string('versao');
                $table->string('sistema_operacional')->default('windows');
                $table->string('arquivo_path');
                $table->string('tamanho')->nullable();
                $table->integer('contador')->default(0);
                $table->text('changelog')->nullable();
                $table->timestamp('data_lancamento')->useCurrent();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't drop it automatically to prevent data loss in messy situations
    }
};
