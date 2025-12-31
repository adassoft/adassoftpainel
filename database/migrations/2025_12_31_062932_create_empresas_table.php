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
        // Verifica se já existe para evitar erro
        if (!Schema::hasTable('empresa')) {
            Schema::create('empresa', function (Blueprint $table) {
                $table->id('codigo'); // PK 'codigo'
                $table->string('razao_social');
                $table->string('nome_fantasia');
                $table->string('cnpj')->unique();
                $table->string('email')->nullable();
                $table->string('fone')->nullable();

                // Endereço
                $table->string('cep')->nullable();
                $table->string('endereco')->nullable();
                $table->string('numero')->nullable();
                $table->string('bairro')->nullable();
                $table->string('cidade')->nullable();
                $table->string('uf', 2)->nullable();

                // Campos Asaas (já prevendo para não depender de outra migration)
                $table->text('asaas_access_token')->nullable();
                $table->string('asaas_wallet_id')->nullable();

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa');
    }
};
