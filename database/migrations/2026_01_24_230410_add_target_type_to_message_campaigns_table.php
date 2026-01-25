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
        Schema::table('message_campaigns', function (Blueprint $table) {
            $table->string('target_type')->default('license')->after('channels'); // license, lead
            $table->foreignId('target_download_id')->nullable()->after('target_software_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message_campaigns', function (Blueprint $table) {
            $table->dropColumn(['target_type', 'target_download_id']);
        });
    }
};
