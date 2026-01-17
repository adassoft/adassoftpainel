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
        Schema::table('terminais_software', function (Blueprint $table) {
            if (!Schema::hasColumn('terminais_software', 'instalacao_id')) {
                $table->string('instalacao_id', 255)->nullable()->after('ativo');
            }
            if (!Schema::hasColumn('terminais_software', 'ip_origem')) {
                $table->string('ip_origem', 45)->nullable()->after('ativo');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('terminais_software', function (Blueprint $table) {
            $table->dropColumn(['instalacao_id', 'ip_origem']);
        });
    }
};
