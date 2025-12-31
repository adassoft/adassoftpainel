<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabela EMPRESA
        // (Já criada corretamente pelo script 2024_01_02_000000_create_legacy_tables.php)

        // 2. Table: usuario
        if (!Schema::hasTable('usuario')) {
            Schema::create('usuario', function (Blueprint $table) {
                $table->integer('id')->autoIncrement()->primary();
                $table->string('nome', 255);
                $table->string('login', 255);
                $table->string('senha', 255);
                $table->date('data');
                $table->string('uf', 255);
                $table->string('acesso', 255);
                $table->string('status', 20)->default('Ativo');
                $table->string('email', 255);
                $table->string('cnpj', 255);
                $table->string('foto', 255)->nullable();
            });
        } else {
            Schema::table('usuario', function (Blueprint $table) {
                if (!Schema::hasColumn('usuario', 'nome'))
                    $table->string('nome', 255)->nullable();
                if (!Schema::hasColumn('usuario', 'login'))
                    $table->string('login', 255)->nullable();
                if (!Schema::hasColumn('usuario', 'senha'))
                    $table->string('senha', 255)->nullable();
                if (!Schema::hasColumn('usuario', 'data'))
                    $table->date('data')->nullable();
                if (!Schema::hasColumn('usuario', 'uf'))
                    $table->string('uf', 255)->nullable();
                if (!Schema::hasColumn('usuario', 'acesso'))
                    $table->string('acesso', 255)->nullable();
                if (!Schema::hasColumn('usuario', 'status'))
                    $table->string('status', 20)->default('Ativo');
                if (!Schema::hasColumn('usuario', 'email'))
                    $table->string('email', 255)->nullable();
                if (!Schema::hasColumn('usuario', 'cnpj'))
                    $table->string('cnpj', 255)->nullable();
                if (!Schema::hasColumn('usuario', 'foto'))
                    $table->string('foto', 255)->nullable();
            });
        }

        // --- TABLES FROM ADASSOFT_DB.SQL THAT WERE MISSING ---

        // Table: api_keys
        if (!Schema::hasTable('api_keys')) {
            Schema::create('api_keys', function (Blueprint $table) {
                $table->id();
                $table->integer('software_id');
                $table->integer('empresa_codigo')->nullable();
                $table->string('label')->nullable();
                $table->char('key_hash', 64);
                $table->string('key_hint', 16)->nullable();
                $table->longText('scopes')->nullable();
                $table->enum('status', ['ativo', 'inativo', 'revogado'])->default('ativo');
                $table->dateTime('expires_at')->nullable();
                $table->unsignedInteger('use_count')->default(0);
                $table->dateTime('last_used_at')->nullable();
                $table->string('last_ip', 64)->nullable();
                $table->dateTime('created_at')->useCurrent();
                $table->integer('created_by')->nullable();
            });
        }

        // Table: api_key_logs
        if (!Schema::hasTable('api_key_logs')) {
            Schema::create('api_key_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('api_key_id')->nullable();
                $table->integer('software_id')->nullable();
                $table->integer('empresa_codigo')->nullable();
                $table->string('endpoint', 64)->nullable();
                $table->boolean('success')->default(true);
                $table->string('ip', 64)->nullable();
                $table->string('note')->nullable();
                $table->dateTime('created_at')->useCurrent();
            });
        }

        // Table: api_login_controle
        if (!Schema::hasTable('api_login_controle')) {
            Schema::create('api_login_controle', function (Blueprint $table) {
                $table->integer('id')->autoIncrement()->primary();
                $table->string('email', 150);
                $table->string('ip', 45);
                $table->integer('tentativas')->default(0);
                $table->dateTime('bloqueado_ate')->nullable();
                $table->dateTime('atualizado_em')->useCurrent()->useCurrentOnUpdate();
            });
        }

        // Table: api_tokens
        if (!Schema::hasTable('api_tokens')) {
            Schema::create('api_tokens', function (Blueprint $table) {
                $table->string('token')->primary();
                $table->integer('user_id');
                $table->dateTime('expires_at');
                $table->boolean('revoked')->default(false);
                $table->dateTime('created_at')->useCurrent();
            });
        }

        // Table: configuracoes
        if (!Schema::hasTable('configuracoes')) {
            Schema::create('configuracoes', function (Blueprint $table) {
                $table->integer('id')->autoIncrement()->primary();
                $table->string('chave', 50);
                $table->text('valor')->nullable();
                $table->timestamp('data_atualizacao')->useCurrent()->useCurrentOnUpdate();
            });
        }

        // Table: config_contratos
        if (!Schema::hasTable('config_contratos')) {
            Schema::create('config_contratos', function (Blueprint $table) {
                $table->integer('id')->primary();
                $table->text('contrato');
                $table->timestamp('data_atualizacao')->useCurrent()->useCurrentOnUpdate();
            });
        }

        // Table: contratos
        if (!Schema::hasTable('contratos')) {
            Schema::create('contratos', function (Blueprint $table) {
                $table->integer('id')->autoIncrement()->primary();
                $table->string('cnpj_cliente', 14);
                $table->integer('software_id')->nullable();
                $table->integer('dia_vencimento');
                $table->date('data_contrato')->nullable();
                $table->string('validade');
                $table->string('recorrencia', 20);
                $table->decimal('valor_adesao', 10, 2)->default(0.00);
                $table->decimal('valor_recorrente', 10, 2);
                $table->string('contrato_anexo')->nullable();
                $table->string('chave_autorizacao');
            });
        }

        // Table: downloads
        if (!Schema::hasTable('downloads')) {
            Schema::create('downloads', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        // Table: downloads_extras
        if (!Schema::hasTable('downloads_extras')) {
            Schema::create('downloads_extras', function (Blueprint $table) {
                $table->integer('id')->autoIncrement()->primary();
                $table->string('titulo');
                $table->text('descricao')->nullable();
                $table->string('categoria', 100)->default('Geral');
                $table->string('versao', 50)->nullable();
                $table->string('tamanho', 50)->nullable();
                $table->string('arquivo_path');
                $table->boolean('publico')->default(true);
                $table->integer('contador')->default(0);
                $table->dateTime('data_cadastro')->useCurrent();
                $table->dateTime('data_atualizacao')->nullable();
            });
        }

        // Table: email_templates
        if (!Schema::hasTable('email_templates')) {
            Schema::create('email_templates', function (Blueprint $table) {
                $table->integer('id')->autoIncrement()->primary();
                $table->string('nome', 120);
                $table->string('slug', 80);
                $table->string('assunto');
                $table->mediumText('html_body')->nullable();
                $table->text('text_body')->nullable();
                $table->text('variaveis_suportadas')->nullable();
                $table->dateTime('atualizado_em')->useCurrent()->useCurrentOnUpdate();
                $table->dateTime('criado_em')->useCurrent();
            });
        }

        // Table: etiquetas
        if (!Schema::hasTable('etiquetas')) {
            Schema::create('etiquetas', function (Blueprint $table) {
                $table->integer('id')->autoIncrement()->primary();
                $table->float('margem_superior', 7, 2);
                $table->float('margem_lateral', 7, 2);
                $table->integer('densidade_vertical');
                $table->integer('densidade_horizontal');
                $table->float('altura', 7, 2);
                $table->float('largura', 7, 2);
                $table->integer('etiquetas_por_linha');
                $table->integer('linhas_por_pagina');
                $table->string('codigo_etiqueta');
                $table->string('codigo_barras');
                $table->string('descricao');
                $table->float('preco', 7, 2);
            });
        }

        // Table: gateways
        if (!Schema::hasTable('gateways')) {
            Schema::create('gateways', function (Blueprint $table) {
                $table->integer('id')->autoIncrement()->primary();
                $table->string('gateway_name', 100);
                $table->boolean('active')->default(false);
                $table->decimal('min_recharge', 10, 2)->default(5.00);
                $table->char('producao', 1)->default('n');
                $table->string('access_token')->nullable();
                $table->string('public_key')->nullable();
                $table->string('client_id')->nullable();
                $table->string('client_secret')->nullable();
                $table->string('webhook_secret')->nullable();
                $table->string('wallet_id')->nullable();
                $table->dateTime('created_at')->useCurrent();
                $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
            });
        }

        // Table: historico_creditos
        if (!Schema::hasTable('historico_creditos')) {
            Schema::create('historico_creditos', function (Blueprint $table) {
                $table->integer('id')->autoIncrement()->primary();
                $table->string('empresa_cnpj', 20);
                $table->integer('usuario_id')->nullable();
                $table->enum('tipo', ['entrada', 'saida']);
                $table->decimal('valor', 10, 2);
                $table->string('descricao');
                $table->dateTime('data_movimento')->useCurrent();
            });
        }

        // Table: licencas
        if (!Schema::hasTable('licencas')) {
            Schema::create('licencas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('software_id');
                $table->string('cliente_nome')->nullable();
                $table->string('cliente_documento')->nullable();
                $table->string('serial_key');
                $table->string('hardware_fingerprint')->nullable();
                $table->string('empresa_cnpj')->nullable();
                $table->date('data_expiracao')->nullable();
                $table->enum('status', ['Ativa', 'Expirada', 'Bloqueada', 'Cancelada'])->default('Ativa');
                $table->string('tipo_recorrencia')->nullable();
                $table->timestamp('data_ativacao')->nullable();
                $table->timestamp('ultimo_checkin')->nullable();
                $table->timestamps();
            });
        }

        // Table: licenca_instalacoes
        if (!Schema::hasTable('licenca_instalacoes')) {
            Schema::create('licenca_instalacoes', function (Blueprint $table) {
                $table->integer('id')->autoIncrement()->primary();
                $table->integer('licenca_id');
                $table->string('serial', 80);
                $table->string('instalacao_id', 120);
                $table->string('mac_address', 50)->nullable();
                $table->string('hostname', 150)->nullable();
                $table->string('ultimo_ip', 45)->nullable();
                $table->dateTime('primeiro_registro')->useCurrent();
                $table->dateTime('ultimo_registro')->useCurrent()->useCurrentOnUpdate();
            });
        }

        // Table: logs_pedidos
        if (!Schema::hasTable('logs_pedidos')) {
            Schema::create('logs_pedidos', function (Blueprint $table) {
                $table->integer('id')->autoIncrement()->primary();
                $table->integer('pedido_id');
                $table->text('mensagem');
                $table->dateTime('data_log')->useCurrent();
            });
        }

        // Table: log_token_emissoes
        if (!Schema::hasTable('log_token_emissoes')) {
            Schema::create('log_token_emissoes', function (Blueprint $table) {
                $table->integer('id')->autoIncrement()->primary();
                $table->string('email', 150);
                $table->integer('empresa_codigo')->nullable();
                $table->integer('software_id')->nullable();
                $table->string('instalacao_id', 120)->nullable();
                $table->string('ip', 45)->nullable();
                $table->boolean('sucesso')->default(false);
                $table->string('motivo')->nullable();
                $table->dateTime('criado_em')->useCurrent();
            });
        }

        // Table: log_validacoes
        if (!Schema::hasTable('log_validacoes')) {
            Schema::create('log_validacoes', function (Blueprint $table) {
                $table->integer('id')->autoIncrement()->primary();
                $table->string('serial', 200);
                $table->string('mac_address', 50)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('resultado');
                $table->dateTime('data_validacao')->useCurrent();
            });
        }

        // Table: metrics_events
        if (!Schema::hasTable('metrics_events')) {
            Schema::create('metrics_events', function (Blueprint $table) {
                $table->bigInteger('id')->autoIncrement()->primary();
                $table->dateTime('created_at')->useCurrent();
                $table->string('event_type', 50);
                $table->integer('empresa_codigo')->nullable();
                $table->integer('software_id')->nullable();
                $table->integer('licenca_id')->nullable();
                $table->integer('usuario_id')->nullable();
                $table->string('status', 50)->nullable();
                $table->decimal('valor', 12, 2)->nullable();
                $table->longText('payload')->nullable(); // JSON
            });
        }

        // Table: noticias
        if (!Schema::hasTable('noticias')) {
            Schema::create('noticias', function (Blueprint $table) {
                $table->integer('id')->autoIncrement()->primary();
                $table->integer('software_id');
                $table->string('titulo', 200);
                $table->text('conteudo');
                $table->string('link_acao')->nullable();
                $table->enum('publico_alvo', ['todos', 'revenda', 'cliente'])->default('todos');
                $table->enum('prioridade', ['alta', 'normal', 'baixa'])->default('normal');
                $table->boolean('ativa')->default(true);
                $table->dateTime('data_criacao')->useCurrent();
            });
        }

        // Table: noticias_lidas
        if (!Schema::hasTable('noticias_lidas')) {
            Schema::create('noticias_lidas', function (Blueprint $table) {
                $table->integer('id')->autoIncrement()->primary();
                $table->integer('noticia_id');
                $table->integer('usuario_id');
                $table->dateTime('data_leitura')->useCurrent();
            });
        }

        // Table: notificacoes_licencas
        if (!Schema::hasTable('notificacoes_licencas')) {
            Schema::create('notificacoes_licencas', function (Blueprint $table) {
                $table->integer('id')->autoIncrement()->primary();
                $table->integer('licenca_id');
                $table->integer('empresa_codigo');
                $table->date('data_expiracao');
                $table->integer('alerta_dias');
                $table->string('email_destino', 190)->nullable();
                $table->dateTime('enviado_em')->useCurrent();
                $table->enum('status_envio', ['sucesso', 'falha'])->default('sucesso');
                $table->string('detalhes')->nullable();
            });
        }

        // Table: representante
        if (!Schema::hasTable('representante')) {
            Schema::create('representante', function (Blueprint $table) {
                $table->integer('id')->autoIncrement()->primary();
                $table->string('cnpj', 18);
                $table->string('razao', 255);
                $table->string('fantasia')->nullable();
                $table->string('telefone', 20)->nullable();
            });
        }

        // Table: softhouse
        if (!Schema::hasTable('softhouse')) {
            Schema::create('softhouse', function (Blueprint $table) {
                $table->integer('id')->autoIncrement()->primary();
                $table->string('cnpj', 18);
                $table->string('razao', 255);
                $table->string('fantasia')->nullable();
                $table->string('ie', 20)->nullable();
                $table->char('estado', 2);
                $table->string('cidade', 100);
                $table->string('endereco', 255);
                $table->string('numero', 10);
                $table->string('cep', 9);
                $table->string('telefone', 20)->nullable();
            });
        }

        // Table: terminais
        if (!Schema::hasTable('terminais')) {
            Schema::create('terminais', function (Blueprint $table) {
                $table->integer('CODIGO')->primary();
                $table->integer('FK_EMPRESA');
                $table->string('MAC', 35);
                $table->string('NOME_COMPUTADOR', 50);
            });
        }

        // Table: terminais_software
        if (!Schema::hasTable('terminais_software')) {
            Schema::create('terminais_software', function (Blueprint $table) {
                $table->integer('id')->autoIncrement()->primary();
                $table->integer('terminal_codigo');
                $table->integer('licenca_id');
                $table->dateTime('data_vinculo')->useCurrent();
                $table->dateTime('ultima_atividade')->nullable();
                $table->boolean('ativo')->default(true);
            });
        }

        // Table: usuario_recuperacao
        if (!Schema::hasTable('usuario_recuperacao')) {
            Schema::create('usuario_recuperacao', function (Blueprint $table) {
                $table->integer('id')->autoIncrement()->primary();
                $table->integer('usuario_id');
                $table->string('codigo', 10);
                $table->dateTime('expira_em');
                $table->boolean('usado')->default(false);
                $table->dateTime('criado_em')->useCurrent();
            });
        }

        // Table: verificacao_cadastro
        if (!Schema::hasTable('verificacao_cadastro')) {
            Schema::create('verificacao_cadastro', function (Blueprint $table) {
                $table->string('email', 150)->primary();
                $table->string('codigo', 10);
                $table->dateTime('expiracao');
            });
        }

        // 3. Table: softwares
        if (!Schema::hasTable('softwares')) {
            Schema::create('softwares', function (Blueprint $table) {
                $table->integer('id')->autoIncrement()->primary();
                $table->string('codigo', 50);
                $table->string('nome_software', 255);
                $table->string('categoria', 100)->default('Geral');
                $table->string('imagem', 255)->nullable();
                $table->string('imagem_destaque', 255)->nullable();
                $table->mediumText('descricao')->nullable();
                $table->mediumText('pagina_vendas_html')->nullable();
                $table->string('url_download', 255)->nullable();
                $table->string('tamanho_arquivo', 50)->nullable();
                $table->string('versao', 50);
                $table->boolean('status')->default(true);
                $table->string('api_key_hash', 128)->nullable();
                $table->string('api_key_hint', 16)->nullable();
                $table->dateTime('api_key_gerada_em')->nullable();
                $table->dateTime('data_cadastro')->useCurrent();
            });
        }

        // 4. Table: planos
        if (!Schema::hasTable('planos')) {
            Schema::create('planos', function (Blueprint $table) {
                $table->integer('id')->autoIncrement()->primary();
                $table->string('nome_plano', 255);
                $table->integer('software_id');
                $table->string('recorrencia', 255);
                $table->string('valor', 255);
                $table->boolean('status')->default(true);
                $table->dateTime('data_cadastro')->useCurrent();
            });
        }

        // 5. Table: planos_revenda
        if (!Schema::hasTable('planos_revenda')) {
            Schema::create('planos_revenda', function (Blueprint $table) {
                $table->integer('id')->autoIncrement()->primary();
                $table->string('cnpj_revenda', 20);
                $table->integer('plano_id');
                $table->decimal('valor_venda', 10, 2)->default(0.00);
                $table->boolean('ativo')->default(true);
                $table->dateTime('data_atualizacao')->nullable();
            });
        }

        // 6. Table: pedido
        if (!Schema::hasTable('pedido')) {
            Schema::create('pedido', function (Blueprint $table) {
                $table->integer('id')->autoIncrement()->primary();
                $table->date('data');
                $table->string('cod_transacao', 255);
                $table->string('cnpj', 255);
                $table->string('recorrencia', 255);
                $table->string('valor', 255);
                $table->string('situacao', 255);
                $table->dateTime('data_pagamento')->nullable();
                $table->string('cnpj_revenda', 20)->nullable();
                $table->integer('software_id')->nullable();
                $table->integer('plano_id')->nullable();
                $table->string('status_entrega', 50)->default('pendente');
                $table->string('serial_gerado', 255)->nullable();
            });
        }

        // 7. Table: historico_seriais
        if (!Schema::hasTable('historico_seriais')) {
            Schema::create('historico_seriais', function (Blueprint $table) {
                $table->integer('id')->autoIncrement()->primary();
                $table->integer('empresa_codigo');
                $table->string('cnpj_revenda', 20)->nullable();
                $table->integer('software_id');
                $table->string('serial_gerado', 200);
                $table->dateTime('data_geracao')->useCurrent();
                $table->date('validade_licenca');
                $table->integer('terminais_permitidos')->default(1);
                $table->boolean('ativo')->default(true);
                $table->text('observacoes')->nullable();
            });
        }

        // 8. Table: licencas_ativas
        if (!Schema::hasTable('licencas_ativas')) {
            Schema::create('licencas_ativas', function (Blueprint $table) {
                $table->integer('id')->autoIncrement()->primary();
                $table->integer('empresa_codigo');
                $table->string('cnpj_revenda', 20)->nullable();
                $table->integer('software_id');
                $table->string('serial_atual', 200);
                $table->dateTime('data_criacao')->nullable();
                $table->dateTime('data_ativacao')->useCurrent();
                $table->date('data_expiracao');
                $table->dateTime('data_ultima_renovacao')->nullable();
                $table->integer('terminais_utilizados')->default(0);
                $table->integer('terminais_permitidos')->default(1);
                $table->enum('status', ['ativo', 'expirado', 'suspenso', 'cancelado'])->default('ativo');
                $table->text('observacoes')->nullable();
            });
        }

        // 9. Table: revenda_transacoes (Added)
        if (!Schema::hasTable('revenda_transacoes')) {
            Schema::create('revenda_transacoes', function (Blueprint $table) {
                $table->integer('id')->autoIncrement()->primary();
                $table->integer('usuario_id');
                $table->enum('tipo', ['credito', 'debito']);
                $table->decimal('valor', 10, 2);
                $table->decimal('saldo_anterior', 10, 2);
                $table->decimal('saldo_novo', 10, 2);
                $table->string('descricao', 255);
                $table->integer('referencia_pedido_id')->nullable();
                $table->dateTime('data_transacao')->useCurrent();
            });
        }

        // 10. Table: revenda_config (Added)
        if (!Schema::hasTable('revenda_config')) {
            Schema::create('revenda_config', function (Blueprint $table) {
                $table->integer('id')->autoIncrement()->primary();
                $table->integer('usuario_id');
                $table->string('nome_sistema', 100)->default('Shield System');
                $table->string('slogan', 255)->default('Segurança e Gestão de Licenças');
                $table->string('logo_path', 255)->default('favicon.svg');
                $table->string('cor_primaria_gradient_start', 20)->default('#1a2980');
                $table->string('cor_primaria_gradient_end', 20)->default('#26d0ce');
                $table->string('dominios', 255)->nullable();
                $table->boolean('ativo')->default(true);
                $table->longText('dados_pendentes')->nullable();
                $table->enum('status_aprovacao', ['aprovado', 'pendente', 'rejeitado', 'com_pendencia'])->default('aprovado');
                $table->text('mensagem_rejeicao')->nullable();
            });
        }

        // 11. Table: revenda_config_historico (Added)
        if (!Schema::hasTable('revenda_config_historico')) {
            Schema::create('revenda_config_historico', function (Blueprint $table) {
                $table->integer('id')->autoIncrement()->primary();
                $table->integer('revenda_config_id');
                $table->enum('acao', ['solicitacao', 'aprovacao', 'rejeicao', 'pendencia', 'analise_ia']);
                $table->text('mensagem')->nullable();
                $table->integer('admin_id')->nullable();
                $table->dateTime('data_registro')->useCurrent();
            });
        }

        // Additional Missing Tables from Legacy
        if (!Schema::hasTable('downloads')) {
            Schema::create('downloads', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Dropping in reverse order or just drop existing ones
        // Since this is a sync migration, usually down() might not drop everything perfectly or just drop the added ones.
        // For safety, let's list them.
        Schema::dropIfExists('revenda_config_historico');
        Schema::dropIfExists('revenda_config');
        Schema::dropIfExists('revenda_transacoes');
        Schema::dropIfExists('licencas_ativas');
        Schema::dropIfExists('historico_seriais');
        Schema::dropIfExists('pedido');
        Schema::dropIfExists('planos_revenda');
        Schema::dropIfExists('planos');
        Schema::dropIfExists('softwares');
        Schema::dropIfExists('usuario');
        Schema::dropIfExists('empresa');

        // Dropping newly added tables
        Schema::dropIfExists('verificacao_cadastro');
        Schema::dropIfExists('usuario_recuperacao');
        Schema::dropIfExists('terminais_software');
        Schema::dropIfExists('terminais');
        Schema::dropIfExists('softhouse');
        Schema::dropIfExists('representante');
        Schema::dropIfExists('notificacoes_licencas');
        Schema::dropIfExists('noticias_lidas');
        Schema::dropIfExists('noticias');
        Schema::dropIfExists('metrics_events');
        Schema::dropIfExists('log_validacoes');
        Schema::dropIfExists('log_token_emissoes');
        Schema::dropIfExists('logs_pedidos');
        Schema::dropIfExists('licenca_instalacoes');
        Schema::dropIfExists('licencas');
        Schema::dropIfExists('historico_creditos');
        Schema::dropIfExists('gateways');
        Schema::dropIfExists('etiquetas');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('downloads_extras');
        Schema::dropIfExists('downloads');
        Schema::dropIfExists('contratos');
        Schema::dropIfExists('config_contratos');
        Schema::dropIfExists('configuracoes');
        Schema::dropIfExists('api_tokens');
        Schema::dropIfExists('api_login_controle');
        Schema::dropIfExists('api_key_logs');
        Schema::dropIfExists('api_keys');
    }
};
