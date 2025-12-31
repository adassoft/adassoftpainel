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
        if (!Schema::hasTable('downloads_extras')) {
            Schema::create('downloads_extras', function (Blueprint $table) {
                $table->id();
                $table->string('titulo');
                $table->text('descricao')->nullable();
                $table->string('categoria')->nullable();
                $table->string('versao')->nullable();
                $table->string('tamanho')->nullable();
                $table->boolean('publico')->default(1);
                $table->integer('contador')->default(0);
                $table->string('arquivo_path')->nullable();
                $table->timestamp('data_cadastro')->useCurrent();
                $table->timestamp('data_atualizacao')->useCurrent();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('downloads_extras');
    }
};
