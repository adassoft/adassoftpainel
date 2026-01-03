<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Softwares
        if (Schema::hasTable('softwares')) {
            Schema::table('softwares', function (Blueprint $table) {
                if (!Schema::hasColumn('softwares', 'disponivel_revenda')) {
                    $table->boolean('disponivel_revenda')->default(false)->after('status');
                }
            });
        }

        // 2. Downloads Extras
        if (Schema::hasTable('downloads_extras')) {
            Schema::table('downloads_extras', function (Blueprint $table) {
                if (!Schema::hasColumn('downloads_extras', 'disponivel_revenda')) {
                    $table->boolean('disponivel_revenda')->default(false)->after('publico');
                }
            });
        }
    }

    public function down(): void
    {
        // Seguro para reverter
        if (Schema::hasColumn('softwares', 'disponivel_revenda')) {
            Schema::table('softwares', function (Blueprint $table) {
                $table->dropColumn('disponivel_revenda');
            });
        }
        if (Schema::hasColumn('downloads_extras', 'disponivel_revenda')) {
            Schema::table('downloads_extras', function (Blueprint $table) {
                $table->dropColumn('disponivel_revenda');
            });
        }
    }
};
