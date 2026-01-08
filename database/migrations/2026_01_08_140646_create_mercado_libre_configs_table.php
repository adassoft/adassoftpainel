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
        Schema::create('mercado_libre_configs', function (Blueprint $table) {
            $table->id();
            // Vincula à tabela companies (para multi-tenancy ou revendas)
            // Se 'companies' usar 'codigo' como PK, ajuste. Se for 'id', use foreignId.
            // Olhando os modelos anteriores, 'companies' usa 'codigo' ou 'id'?
            // O modelo Company.php diz que a chave é 'id' padrão do Eloquent, mas existe 'codigo' antigo.
            // Para segurança, vou usar nullable e index, depois confirmo FK.
            $table->foreignId('company_id')->nullable()->index();

            $table->string('app_id')->nullable();
            $table->string('secret_key')->nullable();
            $table->string('redirect_uri')->nullable();

            $table->string('access_token')->nullable();
            $table->string('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->string('ml_user_id')->nullable(); // ID numérico do usuário no ML
            $table->string('ml_user_nickname')->nullable();

            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mercado_libre_configs');
    }
};
