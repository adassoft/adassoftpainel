<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Verificando estrutura da tabela empresa...\n";

if (!\Illuminate\Support\Facades\Schema::hasColumn('empresa', 'revenda_padrao')) {
    echo "Coluna 'revenda_padrao' NÃO encontrada. Adicionando...\n";
    \Illuminate\Support\Facades\DB::statement("ALTER TABLE empresa ADD COLUMN revenda_padrao TINYINT(1) DEFAULT 0");
    echo "Coluna adicionada com sucesso.\n";
} else {
    echo "Coluna 'revenda_padrao' já existe.\n";
}

echo "FIM.\n";
