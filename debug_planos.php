<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Plano;

$planos = Plano::all();
if ($planos->isEmpty()) {
    echo "Nenhum plano encontrado no banco de dados.\n";
} else {
    foreach ($planos as $p) {
        echo "ID: {$p->id} | Nome: {$p->nome_plano} | SoftwareID: {$p->software_id} | Status: '{$p->status}' | Valor: {$p->valor}\n";
    }
}
