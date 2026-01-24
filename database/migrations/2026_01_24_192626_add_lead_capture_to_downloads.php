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
        Schema::table('downloads_extras', function (Blueprint $table) {
            $table->boolean('requires_lead')->default(false)->after('requires_login');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('downloads_extras', function (Blueprint $table) {
            $table->dropColumn('requires_lead');
        });
    }
};
