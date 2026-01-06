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
            $table->foreignId('category_id')->nullable()->constrained('kb_categories')->nullOnDelete();
            $table->string('slug')->nullable()->unique();
            $table->boolean('is_public')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_bases', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['category_id', 'slug', 'is_public']);
        });
    }
};
