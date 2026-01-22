<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = 'tatiqgfrj@gmail.com';
$empresas = \App\Models\Company::where('email', $email)->get();

echo "Total Found: " . $empresas->count() . "\n";
foreach ($empresas as $emp) {
    echo "ID (codigo): {$emp->codigo} | Razao: {$emp->razao} | CNPJ: {$emp->cnpj}\n";
}
