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
        if (!Schema::hasTable('suggestions')) {
            Schema::create('suggestions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->foreign('user_id')->references('id')->on('usuario')->cascadeOnDelete();
                $table->unsignedBigInteger('software_id')->nullable();
                $table->foreign('software_id')->references('id')->on('softwares')->nullOnDelete();
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
        Schema::dropIfExists('suggestions');
    }
};
