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
        Schema::create('seo_entries', function (Blueprint $table) {
            $table->id();

            // Polimorfismo para Models (Produtos, Posts)
            $table->nullableMorphs('model');

            // Para páginas estáticas (ex: '/about')
            $table->string('url_path')->nullable()->index();

            // Metadados
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('keywords')->nullable(); // Separados por vírgula
            $table->string('focus_keyword')->nullable(); // Para análise

            // Avançado
            $table->string('robots')->default('index, follow');
            $table->string('canonical_url')->nullable();
            $table->string('og_image')->nullable();
            $table->json('json_ld')->nullable(); // Schema.org estruturado

            $table->timestamps();

            // Garante que não tenhamos duplicatas para a mesma rota ou modelo
            $table->unique(['url_path']);
            // O morphs já cria index, mas unique composta seria ideal se o banco permitisse nullable em unique de forma portável, 
            // mas no MySQL unique ignora nulls, o que é bom aqui.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_entries');
    }
};
