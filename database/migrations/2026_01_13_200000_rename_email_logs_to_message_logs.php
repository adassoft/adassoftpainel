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
        // 1. Rename table if exists, or create if not (failed previous migration scenario)
        if (Schema::hasTable('email_logs')) {
            Schema::rename('email_logs', 'message_logs');
        } elseif (!Schema::hasTable('message_logs')) {
            Schema::create('message_logs', function (Blueprint $table) {
                $table->id();
                $table->string('recipient');
                $table->string('subject')->nullable();
                $table->text('body')->nullable();
                $table->string('status')->default('pending'); // pending, sent, failed
                $table->text('error_message')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
            });
        }

        // 2. Add 'channel' column
        if (Schema::hasTable('message_logs')) {
            Schema::table('message_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('message_logs', 'channel')) {
                    $table->string('channel')->default('email')->after('id'); // email, whatsapp
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('message_logs')) {
            Schema::table('message_logs', function (Blueprint $table) {
                $table->dropColumn('channel');
            });
            Schema::rename('message_logs', 'email_logs');
        }
    }
};
