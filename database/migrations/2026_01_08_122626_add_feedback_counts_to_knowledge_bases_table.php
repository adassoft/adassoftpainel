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
        Schema::table('knowledge_bases', function (Blueprint $table) {
            $table->unsignedBigInteger('helpful_count')->default(0)->after('is_public');
            $table->unsignedBigInteger('not_helpful_count')->default(0)->after('helpful_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_bases', function (Blueprint $table) {
            $table->dropColumn(['helpful_count', 'not_helpful_count']);
        });
    }
};
