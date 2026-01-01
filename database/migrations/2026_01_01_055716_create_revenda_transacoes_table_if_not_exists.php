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
        if (!Schema::hasTable('revenda_transacoes')) {
            Schema::create('revenda_transacoes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('usuario_id'); // Referência ao ID do usuário (revendedor)
                $table->string('tipo'); // debito, credito
                $table->decimal('valor', 10, 2);
                $table->decimal('saldo_anterior', 10, 2);
                $table->decimal('saldo_novo', 10, 2);
                $table->string('descricao')->nullable();
                $table->string('referencia_pedido_id')->nullable(); // Caso venha de um pedido
                $table->timestamp('data_transacao')->useCurrent();

                // Index
                $table->index('usuario_id');
                // Se quiser FK estrita:
                // $table->foreign('usuario_id')->references('id')->on('usuario')->onDelete('cascade'); 
                // Mas tabela users do Laravel pode ser 'users' ou 'usuario' (legado). Vou deixar apenas index por segurança.
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Não faz drop pois queremos preservar dados se for rollback safe
        // Schema::dropIfExists('revenda_transacoes');
    }
};
