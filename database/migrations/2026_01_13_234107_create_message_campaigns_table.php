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
        Schema::create('message_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Identificação interna
            $table->text('message'); // Conteúdo da mensagem

            $table->json('channels')->nullable(); // ['whatsapp', 'email', 'sms']

            // Filtros
            $table->foreignId('target_software_id')->nullable()->index(); // Removed constrained() to avoid 150 error temporarily
            $table->string('target_license_status')->default('ativo'); // ativo, bloqueado, todos

            // Controle
            $table->dateTime('scheduled_at')->nullable();
            $table->string('status')->default('draft'); // draft, pending, processing, completed, failed

            $table->integer('processed_count')->default(0);
            $table->integer('total_targets')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_campaigns');
    }
};
