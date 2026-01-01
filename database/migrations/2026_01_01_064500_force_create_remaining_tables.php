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
        // 1. Legal Pages
        if (!Schema::hasTable('legal_pages')) {
            Schema::create('legal_pages', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->longText('content');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Knowledge Bases
        if (!Schema::hasTable('knowledge_bases')) {
            Schema::create('knowledge_bases', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->longText('content');
                $table->json('tags')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 3. Tickets
        if (!Schema::hasTable('tickets')) {
            Schema::create('tickets', function (Blueprint $table) {
                $table->charset = 'utf8';
                $table->collation = 'utf8_unicode_ci';

                $table->id();
                // user_id sem constraint rígida para evitar falhas se usuario/users diferir em prod
                $table->integer('user_id')->index();

                // software_id
                $table->unsignedBigInteger('software_id')->nullable();
                if (Schema::hasTable('softwares')) {
                    // Tenta adicionar FK se tabela existe, senão deixa sem
                    // A migration original tinha constraint desativada, manteremos assim ou verificaremos
                    // Mas para garantir criação, melhor sem constraint rígida aqui se o ambiente for frágil
                }

                $table->string('subject');
                $table->longText('description');
                $table->enum('status', ['open', 'in_progress', 'answered', 'closed'])->default('open');
                $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('low');

                $table->timestamp('closed_at')->nullable();
                $table->timestamps();
            });
        }

        // 4. Ticket Messages (depende de tickets)
        if (!Schema::hasTable('ticket_messages')) {
            Schema::create('ticket_messages', function (Blueprint $table) {
                $table->charset = 'utf8';
                $table->collation = 'utf8_unicode_ci';

                $table->id();
                $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
                $table->integer('user_id')->nullable();

                $table->longText('content');
                $table->json('attachments')->nullable();

                $table->timestamps();
            });
        }

        // 5. Suggestion Votes (depende de suggestions e usuario)
        if (!Schema::hasTable('suggestion_votes')) {
            Schema::create('suggestion_votes', function (Blueprint $table) {
                $table->id();

                // Check user table
                if (Schema::hasTable('usuario')) {
                    $table->integer('user_id');
                    $table->foreign('user_id')->references('id')->on('usuario')->cascadeOnDelete();
                } else {
                    $table->integer('user_id');
                }

                // Check suggestions table (created in previous migration step)
                if (Schema::hasTable('suggestions')) {
                    $table->foreignId('suggestion_id')->constrained('suggestions')->cascadeOnDelete();
                } else {
                    $table->unsignedBigInteger('suggestion_id');
                }

                $table->timestamps();

                $table->unique(['user_id', 'suggestion_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suggestion_votes');
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('knowledge_bases');
        Schema::dropIfExists('legal_pages');
    }
};
