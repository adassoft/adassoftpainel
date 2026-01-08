<?php

use Illuminate\Support\Facades\DB;
use App\Models\CreditHistory;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "Testing DB Connection...\n";
    $databases = DB::select('SHOW DATABASES');
    echo "Connected. Databases found: " . count($databases) . "\n";

    echo "Checking columns in historico_creditos...\n";
    $columns = DB::select('DESCRIBE historico_creditos');
    foreach ($columns as $col) {
        echo "- " . $col->Field . "\n";
    }

    echo "Attempting Insert via Query Builder...\n";
    DB::table('historico_creditos')->insert([
        'empresa_cnpj' => '04733736000120',
        'usuario_id' => 1,
        'tipo' => 'entrada',
        'valor' => 10,
        'descricao' => 'Teste Debug Script',
        'data_movimento' => now()
    ]);
    echo "Query Builder Insert Success!\n";

    echo "Attempting Insert via Eloquent...\n";
    CreditHistory::create([
        'empresa_cnpj' => '04733736000120',
        'usuario_id' => 1,
        'tipo' => 'entrada',
        'valor' => 10,
        'descricao' => 'Teste Eloquent Script',
        'data_movimento' => now()
    ]);
    echo "Eloquent Insert Success!\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString();
}
