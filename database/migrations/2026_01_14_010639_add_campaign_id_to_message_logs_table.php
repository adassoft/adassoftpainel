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
        Schema::table('message_logs', function (Blueprint $table) {
            $table->foreignId('message_campaign_id')->nullable()->after('id')->constrained('message_campaigns')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message_logs', function (Blueprint $table) {
            $table->dropForeign(['message_campaign_id']);
            $table->dropColumn('message_campaign_id');
        });
    }
};
