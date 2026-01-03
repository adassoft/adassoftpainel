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
        Schema::table('empresa', function (Blueprint $table) {
            if (!Schema::hasColumn('empresa', 'asaas_mode')) {
                $table->string('asaas_mode')->default('production')->after('asaas_wallet_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            if (Schema::hasColumn('empresa', 'asaas_mode')) {
                $table->dropColumn('asaas_mode');
            }
        });
    }
};
