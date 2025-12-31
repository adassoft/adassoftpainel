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
            if (!Schema::hasColumn('softwares', 'categoria')) {
                $table->string('categoria')->nullable();
            }
            if (!Schema::hasColumn('softwares', 'imagem')) {
                $table->string('imagem')->nullable();
            }
            if (!Schema::hasColumn('softwares', 'imagem_destaque')) {
                $table->string('imagem_destaque')->nullable();
            }
            if (!Schema::hasColumn('softwares', 'descricao')) {
                $table->text('descricao')->nullable();
            }
            if (!Schema::hasColumn('softwares', 'pagina_vendas_html')) {
                $table->longText('pagina_vendas_html')->nullable();
            }
            if (!Schema::hasColumn('softwares', 'url_download')) {
                $table->string('url_download')->nullable();
            }
            if (!Schema::hasColumn('softwares', 'tamanho_arquivo')) {
                $table->string('tamanho_arquivo')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('softwares', function (Blueprint $table) {
            $table->dropColumn([
                'categoria',
                'imagem',
                'imagem_destaque',
                'descricao',
                'pagina_vendas_html',
                'url_download',
                'tamanho_arquivo'
            ]);
        });
    }
};
