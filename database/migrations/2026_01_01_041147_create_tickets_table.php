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
        Schema::create('tickets', function (Blueprint $table) {
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';

            $table->id();
            $table->foreignId('user_id'); // Sem constraint referencial por enquanto (evitar erro 1005/1452)
            $table->index('user_id');
            $table->foreignId('software_id')->nullable(); // Constraint desativada para debug de Erro 1005

            $table->string('subject');
            $table->longText('description'); // Descrição inicial do problema
            $table->enum('status', ['open', 'in_progress', 'answered', 'closed'])->default('open');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('low');

            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
