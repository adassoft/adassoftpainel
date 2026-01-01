<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $columns = \Illuminate\Support\Facades\DB::select("DESCRIBE revenda_config");
    foreach ($columns as $col) {
        echo $col->Field . "\n";
    }
} catch (\Exception $e) {
    echo "Erro: " . $e->getMessage();
}
