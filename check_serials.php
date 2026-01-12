<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$licenses = App\Models\License::all();
foreach ($licenses as $l) {
    if (empty($l->serial_atual)) {
        echo "ID {$l->id}: SEM SERIAL\n";
        continue;
    }
    $exists = App\Models\SerialHistory::where('serial_gerado', $l->serial_atual)->exists();
    echo "ID {$l->id}: {$l->serial_atual} -> " . ($exists ? 'OK' : 'MISSING') . "\n";
}
