<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$columns = DB::select('DESCRIBE knowledge_bases');
foreach ($columns as $col) {
    echo $col->Field . " (" . $col->Type . ")\n";
}
