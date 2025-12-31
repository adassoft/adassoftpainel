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
        if (!Schema::hasTable('usuario')) {
            Schema::create('usuario', function (Blueprint $table) {
                $table->id();
                $table->string('nome');
                $table->string('login')->unique();
                $table->string('email')->unique();
                $table->string('senha');
                $table->string('acesso')->default('2'); // 1=Admin, 2=Revenda, etc
                $table->string('status')->default('Ativo');
                $table->string('cnpj')->nullable();
                $table->string('foto')->nullable();
                $table->string('uf')->nullable();
                $table->timestamp('data')->useCurrent();
                $table->timestamps(); // Created_at, Updated_at (Model timestamps=false, but good to have)
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuario');
    }
};
