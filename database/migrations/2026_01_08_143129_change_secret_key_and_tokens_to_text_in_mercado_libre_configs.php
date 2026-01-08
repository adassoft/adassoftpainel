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
        Schema::table('mercado_libre_configs', function (Blueprint $table) {
            $table->text('secret_key')->change();
            $table->text('access_token')->change();
            $table->text('refresh_token')->change();
            $table->text('redirect_uri')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mercado_libre_configs', function (Blueprint $table) {
            $table->string('secret_key')->change();
            $table->string('access_token')->change();
            $table->string('refresh_token')->change();
            $table->string('redirect_uri')->change();
        });
    }
};
