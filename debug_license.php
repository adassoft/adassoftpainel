<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$l = App\Models\License::find(5);
if ($l) {
    echo "ID: " . $l->id . "\n";
    echo "Serial: " . $l->serial_atual . "\n";
    echo "Obs: " . $l->observacoes . "\n";
    echo "Created: " . $l->data_criacao . "\n";
} else {
    echo "License 5 not found\n";
}
