<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$sw = \App\Models\Software::find(1);
if ($sw) {
    echo "ID: " . $sw->id . "\n";
    echo "Nome: " . $sw->nome_software . "\n";
    echo "Versao DB: '" . $sw->versao . "'\n";
    echo "Status: " . $sw->status . "\n";
    echo "URL Download: " . $sw->url_download . "\n";

    // Teste de comparação
    $client = '0.0.0.0';
    $server = trim($sw->versao ?? '');
    echo "Comparando '$client' < '$server': " . (version_compare($client, $server, '<') ? 'TRUE' : 'FALSE') . "\n";
} else {
    echo "Software 1 nao encontrado.\n";
}
