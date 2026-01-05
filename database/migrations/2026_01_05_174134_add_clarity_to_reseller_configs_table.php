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
            $table->string('microsoft_clarity_id')->nullable()->after('facebook_pixel_id')->comment('Project ID');
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
