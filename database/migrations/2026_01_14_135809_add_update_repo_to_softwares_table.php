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
            $table->unsignedInteger('id_update_repo')->nullable()->after('id_download_repo');
            // Como a tabela de downloads e softwares usam engines diferentes ou legacy, e o download_extras é MyISAM ou InnoDB?
            // Software é InnoDB. DownloadsExtras é InnoDB? Assumindo que sim.
            // Mas Download::class aponta para 'downloads_extras'.
            // Vamos apenas criar o campo indexado.
            $table->index('id_update_repo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('softwares', function (Blueprint $table) {
            $table->dropColumn('id_update_repo');
        });
    }
};
