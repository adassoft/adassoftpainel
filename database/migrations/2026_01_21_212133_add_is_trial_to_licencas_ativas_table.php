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
        Schema::table('licencas_ativas', function (Blueprint $table) {
            $table->boolean('is_trial')->default(false)->after('vitalicia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('licencas_ativas', function (Blueprint $table) {
            $table->dropColumn('is_trial');
        });
    }
};
