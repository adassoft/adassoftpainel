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
        Schema::create('kb_category_knowledge_base', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kb_category_id')->constrained('kb_categories')->cascadeOnDelete();
            $table->foreignId('knowledge_base_id')->constrained('knowledge_bases')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Migrate existing data
        $articles = \Illuminate\Support\Facades\DB::table('knowledge_bases')
            ->whereNotNull('category_id')
            ->get();

        foreach ($articles as $article) {
            \Illuminate\Support\Facades\DB::table('kb_category_knowledge_base')->insert([
                'kb_category_id' => $article->category_id,
                'knowledge_base_id' => $article->id,
                'sort_order' => $article->sort_order ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kb_category_knowledge_base');
    }
};
