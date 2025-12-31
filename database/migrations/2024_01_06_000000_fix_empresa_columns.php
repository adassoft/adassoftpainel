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
        Schema::table('empresa', function (Blueprint $table) {
            // Verificar e adicionar colunas que estão faltando, baseado no erro 42S22 e Model Company
            if (!Schema::hasColumn('empresa', 'codigo')) {
                // Se não tiver chave primária 'codigo', vamos assumir que 'id' é a chave ou criar.
                // O model diz primaryKey = 'codigo', mas o migration anterior criou 'id'.
                // Vamos adicionar 'codigo' como alias ou primary se possível, mas como 'id' já existe (AutoIncrement),
                // talvez o código espere 'codigo' explicitamente.
                // No sistema legado, codigo era provavelmente o ID.
                // Vamos adicionar codigo como integer autoincrement se não der conflito, ou apenas integer.
                // Como já tem 'id', vamos manter 'id' e garantir que o model use 'id' ou que 'codigo' seja uma coluna computada/copy.
                // Mas o erro é "Unknown column 'razao'". Focaremos nisso primeiro.
            }

            if (!Schema::hasColumn('empresa', 'razao')) {
                $table->string('razao')->nullable();
            }
            // O migration anterior criou 'razao_social', mas o controller usa 'razao'.
            // Vamos adicionar 'razao' para compatibilidade imediata.

            if (!Schema::hasColumn('empresa', 'data')) {
                $table->timestamp('data')->nullable(); // Data cadastro
            }

            if (!Schema::hasColumn('empresa', 'fone')) {
                $table->string('fone')->nullable();
            }

            if (!Schema::hasColumn('empresa', 'uf')) {
                $table->string('uf')->nullable();
            }

            // Adicionando outros campos úteis do Model Company para evitar erros futuros
            if (!Schema::hasColumn('empresa', 'nterminais'))
                $table->integer('nterminais')->default(1);
            if (!Schema::hasColumn('empresa', 'serial'))
                $table->string('serial')->nullable();
            if (!Schema::hasColumn('empresa', 'software_principal_id'))
                $table->integer('software_principal_id')->nullable();
            if (!Schema::hasColumn('empresa', 'data_ultima_ativacao'))
                $table->timestamp('data_ultima_ativacao')->nullable();
            if (!Schema::hasColumn('empresa', 'validade_licenca'))
                $table->date('validade_licenca')->nullable();
            if (!Schema::hasColumn('empresa', 'bloqueado'))
                $table->boolean('bloqueado')->default(0);
            if (!Schema::hasColumn('empresa', 'cnpj_representante'))
                $table->string('cnpj_representante')->nullable();
            if (!Schema::hasColumn('empresa', 'app_alerta_vencimento'))
                $table->boolean('app_alerta_vencimento')->default(1);
            if (!Schema::hasColumn('empresa', 'app_dias_alerta'))
                $table->integer('app_dias_alerta')->default(5);
            if (!Schema::hasColumn('empresa', 'revenda_padrao'))
                $table->boolean('revenda_padrao')->default(0);
            if (!Schema::hasColumn('empresa', 'asaas_access_token'))
                $table->text('asaas_access_token')->nullable();
            if (!Schema::hasColumn('empresa', 'asaas_wallet_id'))
                $table->string('asaas_wallet_id')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            $table->dropColumn(['razao', 'data', 'fone', 'uf']);
        });
    }
};
