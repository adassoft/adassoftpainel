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
        Schema::table('usuario', function (Blueprint $table) {
            $table->text('bio')->nullable();
            $table->string('job_title')->nullable(); // Especialista Técnico, CEO, etc.
            $table->string('linkedin_url')->nullable();
        });

        Schema::table('knowledge_bases', function (Blueprint $table) {
            $table->foreignId('author_id')->nullable()->constrained('usuario')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_bases', function (Blueprint $table) {
            // Drop foreign key first depending on DB driver, but safe to just drop column in modern Laravel
            $table->dropForeign(['author_id']);
            $table->dropColumn('author_id');
        });

        Schema::table('usuario', function (Blueprint $table) {
            $table->dropColumn(['bio', 'job_title', 'linkedin_url']);
        });
    }
};
