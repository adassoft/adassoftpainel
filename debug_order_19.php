<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$order = App\Models\Order::with(['user.empresa', 'plan.software'])->find(19);

if (!$order) {
    echo "Order 19 not found.\n";
    exit;
}

echo "Order found.\n";
echo "CNPJ (Order): " . $order->cnpj . "\n";
echo "User ID: " . $order->user_id . "\n";
if ($order->user) {
    echo "User found. Empresa ID: " . $order->user->empresa_id . "\n";
    if ($order->user->empresa) {
        echo "Empresa found.\n";
        echo "Codigo: " . $order->user->empresa->codigo . "\n";
        echo "CNPJ (Empresa): " . $order->user->empresa->cnpj . "\n";
    } else {
        echo "Empresa NOT found via relationship.\n";
    }
} else {
    echo "User NOT found.\n";
}

$softwareId = $order->plan?->software_id ?? $order->software_id;
echo "Target Software ID: " . $softwareId . "\n";

// Check licenses for company + software
if ($order->user && $order->user->empresa_id) {
    $code = $order->user->empresa_id;
    $lics = App\Models\License::where('empresa_codigo', $code)->get();
    echo "Licenses found count: " . $lics->count() . "\n";
    foreach ($lics as $l) {
        echo " - Lic ID {$l->id}: Software {$l->software_id}, Exp {$l->data_expiracao}\n";
    }
}
