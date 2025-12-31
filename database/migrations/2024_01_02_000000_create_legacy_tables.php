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
        // 1. Tabela EMPRESA (Revendedores/Clientes)
        if (!Schema::hasTable('empresa')) {
            Schema::create('empresa', function (Blueprint $table) {
                // PK Integer legado
                $table->integer('codigo')->autoIncrement()->primary();

                // Colunas Legadas
                $table->string('cnpj', 20)->nullable();
                $table->string('razao', 50)->nullable();
                $table->string('endereco', 50)->nullable();
                $table->string('cidade', 35)->nullable();
                $table->string('bairro', 35)->nullable();
                $table->string('cep', 20)->nullable();
                $table->string('uf', 2)->nullable();
                $table->string('fone', 20)->nullable();
                $table->string('email', 120)->nullable();
                $table->dateTime('data')->useCurrent()->nullable(); // data_cadastro

                // Campos Específicos do Sistema
                $table->integer('nterminais')->nullable();
                $table->string('serial', 200)->nullable();
                $table->integer('software_principal_id')->nullable();
                $table->dateTime('data_ultima_ativacao')->nullable();
                $table->date('validade_licenca')->nullable();
                $table->string('bloqueado', 1)->nullable();
                $table->string('cnpj_representante', 20)->nullable();
                $table->boolean('app_alerta_vencimento')->default(true);
                $table->integer('app_dias_alerta')->default(5);
                $table->string('status', 20)->default('Ativo');
                $table->decimal('saldo', 10, 2)->default(0.00);
                $table->boolean('revenda_padrao')->default(false);
                $table->text('asaas_access_token')->nullable();
                $table->string('asaas_wallet_id', 255)->nullable();

                // Campos extras mantidos para compatibilidade futura ou Filament (opcionais)
                $table->string('nome_fantasia')->nullable();
                $table->string('complemento')->nullable();
                $table->string('numero')->nullable();
                $table->string('senha')->nullable();
                $table->string('logo')->nullable();
                $table->string('cor_primaria')->nullable();
                $table->string('cor_secundaria')->nullable();
                $table->integer('nivel_acesso')->default(2);

                $table->timestamps(); // Mantém created_at/updated_at por conveniência do Eloquent
            });
        }

        // 2. Tabela PLANOS (Vinculado a softwares)
        // (Já criei no passo anterior, mas vou reforçar caso não tenha rodado)
        if (!Schema::hasTable('planos')) {
            Schema::create('planos', function (Blueprint $table) {
                $table->id();
                $table->string('nome_plano');
                $table->foreignId('software_id')->constrained('softwares')->onDelete('cascade');
                $table->string('recorrencia'); // MENSAL, ANUAL, TRIMESTRAL
                $table->decimal('valor', 10, 2);
                $table->boolean('status')->default(1);
                $table->timestamp('data_cadastro')->useCurrent();
                $table->timestamps();
            });
        }

        // 3. Tabela LICENCAS (Seriais)
        if (!Schema::hasTable('licencas')) {
            Schema::create('licencas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('software_id')->constrained('softwares');
                $table->string('cliente_nome')->nullable(); // Nome do cliente final
                $table->string('cliente_documento')->nullable(); // CPF/CNPJ final
                $table->string('serial_key')->unique(); // O serial em si
                $table->string('hardware_fingerprint')->nullable(); // HWID
                $table->string('empresa_cnpj')->nullable(); // Revenda dona da licença
                $table->date('data_expiracao')->nullable();
                $table->enum('status', ['Ativa', 'Expirada', 'Bloqueada', 'Cancelada'])->default('Ativa');
                $table->string('tipo_recorrencia')->nullable(); // MENSAL, ANUAL
                $table->timestamp('data_ativacao')->nullable();
                $table->timestamp('ultimo_checkin')->nullable();
                $table->timestamps();
            });
        }

        // 4. Tabela PEDIDOS (Compras de créditos/licenças)
        if (!Schema::hasTable('pedido')) {
            Schema::create('pedido', function (Blueprint $table) {
                $table->id();
                $table->string('cod_transacao')->unique();
                $table->string('cnpj')->nullable(); // Quem comprou
                $table->decimal('valor', 10, 2);
                $table->string('status')->default('AGUARDANDO'); // PAGO, CANCELADO
                $table->string('situacao')->nullable(); // Legacy alias for status
                $table->string('recorrencia')->nullable(); // CREDITO, MENSAL...
                $table->text('descricao')->nullable();
                $table->timestamp('data_pagamento')->nullable();
                $table->timestamp('data')->useCurrent(); // data pedido
                $table->timestamps();
            });
        }

        // 5. Tabela GATEWAYS (Configurações de Pagamento)
        if (!Schema::hasTable('gateways')) {
            Schema::create('gateways', function (Blueprint $table) {
                $table->id();
                $table->string('gateway_name'); // Asaas, MP
                $table->text('access_token')->nullable();
                $table->string('public_key')->nullable(); // ou wallet_id
                $table->string('wallet_id')->nullable();
                $table->enum('producao', ['s', 'n'])->default('n');
                $table->boolean('active')->default(0);
                $table->decimal('min_recharge', 10, 2)->default(5.00);
                $table->timestamps();
            });

            // Insert default Asaas
            DB::table('gateways')->insert([
                'gateway_name' => 'Asaas',
                'active' => 1,
                'min_recharge' => 5.00,
                'producao' => 'n'
            ]);
        }

        // 6. Tabela HISTORICO_CREDITOS (Extrato)
        if (!Schema::hasTable('historico_creditos')) {
            Schema::create('historico_creditos', function (Blueprint $table) {
                $table->id();
                $table->string('empresa_cnpj');
                $table->enum('tipo', ['entrada', 'saida']);
                $table->decimal('valor', 10, 2);
                $table->string('descricao')->nullable();
                $table->timestamp('data_movimento')->useCurrent();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historico_creditos');
        Schema::dropIfExists('gateways');
        Schema::dropIfExists('pedido');
        Schema::dropIfExists('licencas');
        Schema::dropIfExists('planos');
        Schema::dropIfExists('empresa');
    }
};
