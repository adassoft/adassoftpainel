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
        Schema::table('softwares', function (Blueprint $table) {
            $table->string('gtin')->nullable()->after('codigo');
            $table->string('google_product_category')->nullable()->after('categoria');
            $table->string('brand')->nullable()->default('AdasSoft')->after('nome_software');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('softwares', function (Blueprint $table) {
            $table->dropColumn(['gtin', 'google_product_category', 'brand']);
        });
    }
};
