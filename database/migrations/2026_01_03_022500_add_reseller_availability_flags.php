<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('softwares', function (Blueprint $table) {
            $table->boolean('disponivel_revenda')->default(false)->after('status');
        });

        Schema::table('downloads_extras', function (Blueprint $table) {
            $table->boolean('disponivel_revenda')->default(false)->after('publico');
        });
    }

    public function down(): void
    {
        Schema::table('softwares', function (Blueprint $table) {
            $table->dropColumn('disponivel_revenda');
        });

        Schema::table('downloads_extras', function (Blueprint $table) {
            $table->dropColumn('disponivel_revenda');
        });
    }
};
