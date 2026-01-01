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
            $table->string('instagram_url')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('email_suporte')->nullable();
            $table->string('endereco')->nullable();
            $table->string('horario_atendimento')->nullable();
            $table->boolean('exibir_documento')->default(true)->comment('Se false, oculta CPF/CNPJ na área pública.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('revenda_config', function (Blueprint $table) {
            $table->dropColumn([
                'instagram_url',
                'facebook_url',
                'linkedin_url',
                'youtube_url',
                'whatsapp',
                'email_suporte',
                'endereco',
                'horario_atendimento',
                'exibir_documento'
            ]);
        });
    }
};
