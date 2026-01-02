<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('revenda_config', function (Blueprint $table) {
            $table->string('icone_path')->nullable()->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('revenda_config', function (Blueprint $table) {
            $table->dropColumn('icone_path');
        });
    }
};
