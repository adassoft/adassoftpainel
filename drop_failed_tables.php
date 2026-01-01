<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "Dropping tables tickets and ticket_messages...\n";
Schema::disableForeignKeyConstraints();
Schema::dropIfExists('ticket_messages');
Schema::dropIfExists('tickets');
Schema::enableForeignKeyConstraints();

echo "Cleaning migrations table...\n";
DB::table('migrations')->where('migration', 'like', '%create_tickets%')->delete();
DB::table('migrations')->where('migration', 'like', '%create_ticket_messages%')->delete();

echo "Done.\n";
