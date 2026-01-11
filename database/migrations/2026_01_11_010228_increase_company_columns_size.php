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
        Schema::table('empresa', function (Blueprint $table) {
            $table->string('razao', 500)->change();
            $table->text('endereco')->change();
            $table->string('cidade', 255)->change();
            $table->string('bairro', 255)->change();
        });
    }

    public function down(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            $table->string('razao', 50)->change();
            $table->string('endereco', 50)->change();
            $table->string('cidade', 35)->change();
            $table->string('bairro', 35)->change();
        });
    }
};
