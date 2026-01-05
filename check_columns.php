<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$columns = \Illuminate\Support\Facades\Schema::getColumnListing('empresa');
echo "Colunas na tabela 'empresa':\n";
foreach ($columns as $col) {
    if ($col === 'numero' || $col === 'nro') {
        echo "ACHEI: " . $col . "\n";
    }
}
print_r($columns);
