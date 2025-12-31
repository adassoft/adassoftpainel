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
            if (!Schema::hasColumn('empresa', 'asaas_access_token')) {
                $table->string('asaas_access_token')->nullable()->after('email');
            }
            if (!Schema::hasColumn('empresa', 'asaas_wallet_id')) {
                $table->string('asaas_wallet_id')->nullable()->after('asaas_access_token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            $table->dropColumn(['asaas_access_token', 'asaas_wallet_id']);
        });
    }
};
