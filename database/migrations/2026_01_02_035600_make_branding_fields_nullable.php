<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('revenda_config', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->change();
            $table->string('icone_path')->nullable()->change();
            $table->string('dominios')->nullable()->change(); // Preventiu também
        });
    }

    public function down(): void
    {
        // Não reverte para evitar problemas de integridade se existirem nulos
    }
};
