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
        Schema::table('revenda_config', function (Blueprint $table) {
            $table->string('google_analytics_id')->nullable()->after('exibir_documento');
            $table->string('facebook_pixel_id')->nullable()->after('google_analytics_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('revenda_config', function (Blueprint $table) {
            //
        });
    }
};
