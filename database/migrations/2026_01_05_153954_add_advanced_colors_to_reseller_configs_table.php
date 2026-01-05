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
        Schema::table('revenda_config', function (Blueprint $table) {
            $table->string('cor_acento')->default('#4e73df')->after('cor_primaria_gradient_end')->comment('Cor para botões e CTAs');
            $table->string('cor_secundaria')->default('#858796')->after('cor_acento')->comment('Cor para textos secundários ou elementos de apoio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('revenda_config', function (Blueprint $table) {
            $table->dropColumn(['cor_acento', 'cor_secundaria']);
        });
    }
};
