<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$key = '5b29e9a9a76dd8554e17f5da7e673a97deb04518bbfe32cf8ee94e51b1e1575a';
$hash = hash('sha256', $key);

echo "Plain Key: " . $key . "\n";
echo "Calculated Hash: " . $hash . "\n";

$record = \App\Models\ApiKey::where('key_hash', $hash)->first();

if ($record) {
    echo "Found Record ID: " . $record->id . " Status: " . $record->status . "\n";
    echo "Software ID linked: " . ($record->software_id ?? 'None') . "\n";

    // Check Software Status
    if ($record->software) {
        echo "Linked Software Status: " . $record->software->status . "\n";
    }
} else {
    echo "Record NOT FOUND for this hash.\n";

    // Debug: Check if the key itself is stored as hash (what user shouldn't do but might happen)
    $directRecord = \App\Models\ApiKey::where('key_hash', $key)->first();
    if ($directRecord) {
        echo "WARNING: The key you provided matches a generic 'key_hash' directly in DB. \n";
        echo "This means the DB expects the HASH of this value, or you are sending the hash as the key.\n";
    }
}
