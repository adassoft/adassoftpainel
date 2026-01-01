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
        Schema::table('softwares', function (Blueprint $table) {
            $table->string('linguagem')->nullable()->after('descricao')->comment('Ex: PHP, Delphi, C#');
            $table->string('plataforma')->nullable()->after('linguagem')->comment('Ex: Web, Desktop, Mobile');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('softwares', function (Blueprint $table) {
            $table->dropColumn(['linguagem', 'plataforma']);
        });
    }
};
