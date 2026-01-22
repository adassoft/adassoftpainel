<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$master = \App\Models\User::where('acesso', 1)->orderBy('id')->first();
echo "Master ID: " . ($master->id ?? 'N/A') . "\n";
echo "Master CNPJ Raw: " . ($master->cnpj ?? 'NULL') . "\n";

if ($master && !empty($master->cnpj)) {
    $cnpj = preg_replace('/\D/', '', $master->cnpj);
    echo "Master CNPJ Clean: " . $cnpj . "\n";

    $planosRevendaCount = \App\Models\PlanoRevenda::where('cnpj_revenda', $cnpj)->count();
    echo "Planos Revenda Count: " . $planosRevendaCount . "\n";

    if ($planosRevendaCount > 0) {
        $pr = \App\Models\PlanoRevenda::where('cnpj_revenda', $cnpj)->first();
        echo "Sample PR Valor: " . $pr->valor_venda . " | Ativo: " . ($pr->ativo ? 'SIM' : 'NAO') . "\n";
    } else {
        echo "Master nao tem planos configurados na tabela plano_revenda.\n";
    }
} else {
    echo "Master nao encontrado ou sem CNPJ.\n";
}
