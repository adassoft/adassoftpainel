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
        // 1. Redirects
        if (!Schema::hasTable('redirects')) {
            Schema::create('redirects', function (Blueprint $table) {
                $table->id();
                $table->string('path')->index()->comment('Caminho antigo ex: /meu-post');
                $table->text('target_url')->comment('URL nova completa ou rota interna');
                $table->integer('status_code')->default(301)->comment('301 para permanente, 302 para temporário');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Suggestions
        if (!Schema::hasTable('suggestions')) {
            Schema::create('suggestions', function (Blueprint $table) {
                $table->id();

                // Foreign Keys - Checks if referenced tables actally exist to avoid errors
                $userIdType = 'integer';

                // Check 'usuario' table for user_id FK
                if (Schema::hasTable('usuario')) {
                    // Assume integer structure as per previous migrations
                    $table->integer('user_id');
                    $table->foreign('user_id')->references('id')->on('usuario')->cascadeOnDelete();
                } else {
                    // Fallback if 'usuario' missing (unlikely but safe)
                    $table->integer('user_id');
                }

                $table->integer('software_id')->nullable();
                if (Schema::hasTable('softwares')) {
                    $table->foreign('software_id')->references('id')->on('softwares')->nullOnDelete();
                }

                $table->string('title');
                $table->text('description');
                $table->enum('status', ['pending', 'voting', 'planned', 'in_progress', 'completed', 'rejected'])->default('pending');
                $table->integer('votes_count')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We generally don't drop these here if they were "forced" creation, 
        // but strictly speaking down() should reverse up().
        // However, since this is a repair migration, we might choose to do nothing
        // or drop them only if we are sure. 
        // Safest is schema::dropIfExists for both.

        Schema::dropIfExists('suggestions');
        Schema::dropIfExists('redirects');
    }
};
