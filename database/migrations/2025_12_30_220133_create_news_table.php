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
        // Verifica se a tabela já existe (caso o banco legado já esteja importado)
        if (!Schema::hasTable('noticias')) {
            Schema::create('noticias', function (Blueprint $table) {
                $table->id();
                $table->foreignId('software_id')->nullable(); // Pode ser nulo para notícias gerais

                $table->string('titulo');
                $table->text('conteudo');
                $table->string('link_acao')->nullable();

                $table->enum('prioridade', ['baixa', 'normal', 'alta'])->default('normal');
                $table->boolean('ativa')->default(true);

                $table->enum('publico', ['revenda', 'todos'])->default('todos');
                $table->enum('tipo', ['manual', 'automatico'])->default('manual');

                $table->timestamps();
            });
        } else {
            // Se já existe, adiciona as colunas novas se faltarem
            Schema::table('noticias', function (Blueprint $table) {
                if (!Schema::hasColumn('noticias', 'publico')) {
                    $table->enum('publico', ['revenda', 'todos'])->default('todos')->after('ativa');
                }
                if (!Schema::hasColumn('noticias', 'tipo')) {
                    $table->enum('tipo', ['manual', 'automatico'])->default('manual')->after('publico');
                }
                if (!Schema::hasColumn('noticias', 'created_at')) {
                    $table->timestamps();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('noticias');
    }
};
