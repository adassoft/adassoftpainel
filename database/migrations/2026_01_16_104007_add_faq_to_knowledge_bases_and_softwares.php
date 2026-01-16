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
            $table->json('faq')->nullable()->after('content');
        });

        Schema::table('softwares', function (Blueprint $table) {
            $table->json('faq')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_bases', function (Blueprint $table) {
            $table->dropColumn('faq');
        });

        Schema::table('softwares', function (Blueprint $table) {
            $table->dropColumn('faq');
        });
    }
};
