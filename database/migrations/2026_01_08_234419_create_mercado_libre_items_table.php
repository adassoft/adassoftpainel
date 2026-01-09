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
        Schema::create('mercado_libre_items', function (Blueprint $table) {
            $table->id();

            // Proprietary Links
            $table->foreignId('company_id')->nullable()->index();
            $table->string('ml_user_id')->nullable()->index();

            // Mercado Libre Data
            $table->string('ml_id')->unique(); // ID do anúncio (MLB...)
            $table->string('title');
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency_id')->default('BRL');
            $table->integer('available_quantity')->default(0);
            $table->integer('sold_quantity')->default(0);
            $table->string('status')->default('active');
            $table->string('permalink', 500)->nullable();
            $table->string('thumbnail', 500)->nullable();

            // Local Mapping (Vinculação)
            // Usando unsignedBigInteger sem constrained estrito para evitar conflitos de nomes de tabela legado
            // O model Download aponta para 'downloads_extras', mas outras tabelas apontam para 'downloads'
            $table->unsignedBigInteger('download_id')->nullable()->index();

            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mercado_libre_items');
    }
};
