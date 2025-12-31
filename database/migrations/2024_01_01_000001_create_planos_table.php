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
        if (!Schema::hasTable('planos')) {
            Schema::create('planos', function (Blueprint $table) {
                $table->id();
                $table->string('nome_plano');
                $table->foreignId('software_id')->constrained('softwares')->onDelete('cascade');
                $table->string('recorrencia'); // MENSAL, TRIMESTRAL, etc
                $table->decimal('valor', 10, 2);
                $table->boolean('status')->default(1);
                $table->timestamp('data_cadastro')->useCurrent();
                // $table->timestamps(); // Legacy table typically doesn't have updated_at
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planos');
    }
};
