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
        Schema::create('redirect_logs', function (Blueprint $table) {
            $table->id();
            $table->string('path')->index();
            $table->integer('hits')->default(1);
            $table->timestamp('last_accessed_at');
            $table->string('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('is_resolved')->default(false)->comment('Se já virou um redirect');
            $table->boolean('is_ignored')->default(false)->comment('Se deve ser ignorado');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('redirect_logs');
    }
};
