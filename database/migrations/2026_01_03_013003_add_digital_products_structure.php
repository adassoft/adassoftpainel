<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Atualizar tabela 'downloads' com campos de produto
        Schema::table('downloads', function (Blueprint $table) {
            if (!Schema::hasColumn('downloads', 'preco')) {
                $table->decimal('preco', 10, 2)->nullable()->default(0.00);
            }
            if (!Schema::hasColumn('downloads', 'is_paid')) {
                $table->boolean('is_paid')->default(false);
            }
            if (!Schema::hasColumn('downloads', 'requires_login')) {
                $table->boolean('requires_login')->default(false);
            }
        });

        // 2. Criar tabela 'orders' (Pedidos)
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('status')->default('pending'); // pending, paid, failed, cancelled
                $table->decimal('total', 10, 2);
                $table->string('payment_method')->nullable(); // pix, credit_card
                $table->string('external_id')->nullable(); // ID do gateway (Asaas, Stripe)
                $table->string('payment_url')->nullable(); // URL para pagar
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
            });
        }

        // 3. Criar tabela 'order_items' (Itens do Pedido)
        if (!Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
                $table->foreignId('download_id')->nullable()->constrained('downloads')->onDelete('set null'); // Produto comprado
                $table->string('product_name'); // Snapshot do nome na hora da compra
                $table->decimal('price', 10, 2); // Snapshot do preço pago
                $table->timestamps();
            });
        }

        // 4. Criar tabela 'user_library' (Meus Produtos)
        // Acesso rápido para verificar se o usuário possui o produto
        if (!Schema::hasTable('user_library')) {
            Schema::create('user_library', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('download_id')->constrained('downloads')->onDelete('cascade');
                $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null'); // Origem da aquisição
                $table->timestamps();

                // Evitar duplicidade: um usuário só tem uma cópia do produto na biblioteca
                $table->unique(['user_id', 'download_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_library');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');

        Schema::table('downloads', function (Blueprint $table) {
            if (Schema::hasColumn('downloads', 'preco')) {
                $table->dropColumn(['preco', 'is_paid', 'requires_login']);
            }
        });
    }
};
