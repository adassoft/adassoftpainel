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
        Schema::table('mercado_libre_configs', function (Blueprint $table) {
            $table->string('site_id')->default('MLB')->after('company_id'); // MLB = Brasil
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mercado_libre_configs', function (Blueprint $table) {
            $table->dropColumn('site_id');
        });
    }
};
