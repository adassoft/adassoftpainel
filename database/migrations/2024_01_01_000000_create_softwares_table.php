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
        if (!Schema::hasTable('softwares')) {
            Schema::create('softwares', function (Blueprint $table) {
                $table->id();
                $table->string('codigo')->nullable(); // Legacy might not be strict
                $table->string('nome_software');
                $table->string('versao')->nullable();
                $table->boolean('status')->default(1);
                $table->string('api_key_hash')->nullable();
                $table->string('api_key_hint')->nullable();
                $table->timestamp('api_key_gerada_em')->nullable();
                $table->timestamp('data_cadastro')->useCurrent();
                $table->timestamps(); // Adds created_at and updated_at
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('softwares');
    }
};
