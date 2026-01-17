<?php
// Carrega o autoloader
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$softwareId = 1; // Assumido do print

echo "Verificando chaves para Software ID: $softwareId\n";

$keys = App\Models\ApiKey::where('software_id', $softwareId)->get();

foreach ($keys as $k) {
    echo "ID: {$k->id}\n";
    echo "Scopes: " . json_encode($k->scopes) . "\n";
    echo "Hash: {$k->key_hash}\n";
    echo "--------------------------\n";
}
