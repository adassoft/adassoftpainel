<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = DB::select('SHOW TABLES');
foreach ($tables as $t) {
    $tableName = reset($t); // Pega o valor da primeira coluna (Tables_in_dbname)

    // Busca colunas com 'credito'
    $cols1 = DB::select("SHOW COLUMNS FROM `$tableName` LIKE '%credito%'");
    if (!empty($cols1)) {
        echo "--- Tabela: $tableName (credito) ---\n";
        foreach ($cols1 as $c)
            echo $c->Field . "\n";
    }

    // Busca colunas com 'saldo'
    $cols2 = DB::select("SHOW COLUMNS FROM `$tableName` LIKE '%saldo%'");
    if (!empty($cols2)) {
        echo "--- Tabela: $tableName (saldo) ---\n";
        foreach ($cols2 as $c)
            echo $c->Field . "\n";
    }
}
