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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('download_id');
            $table->string('empresa')->nullable();
            $table->string('nome');
            $table->string('email');
            $table->string('whatsapp')->nullable();
            $table->string('ip_address')->nullable();
            $table->boolean('converted')->default(false);
            $table->timestamps();

            // Não uso foreign key constraint rígida 'constrained' pq downloads_extras é tabela legacy e pode ter engines diferentes ou problemas.
            // Mas 'downloads_extras' geralmente é InnoDB em Laravel novo.
            // Para garantir, uso index normal.
            $table->index('download_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
