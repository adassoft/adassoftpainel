<?php
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "App Locale: " . config('app.locale') . "\n";
echo "Fallback Locale: " . config('app.fallback_locale') . "\n";
