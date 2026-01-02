<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('revenda_config') && !Schema::hasColumn('revenda_config', 'icone_path')) {
            Schema::table('revenda_config', function (Blueprint $table) {
                $table->string('icone_path')->nullable()->after('logo_path');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('revenda_config') && Schema::hasColumn('revenda_config', 'icone_path')) {
            Schema::table('revenda_config', function (Blueprint $table) {
                $table->dropColumn('icone_path');
            });
        }
    }
};
