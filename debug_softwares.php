<?php

use Illuminate\Support\Facades\Schema;
use App\Models\Software;
use App\Services\ResellerBranding;

// 1. Check if column exists
$hasColumn = Schema::hasColumn('softwares', 'disponivel_revenda');
echo "Column 'disponivel_revenda' exists in 'softwares'? " . ($hasColumn ? 'YES' : 'NO') . "\n";

if ($hasColumn) {
    // 2. Check values
    $counts = Software::all()->groupBy('disponivel_revenda')->map->count();
    echo "Values distribution:\n";
    print_r($counts->toArray());

    // Sample
    $first = Software::first();
    echo "First Software Record: ID {$first->id} - Revenda: " . ($first->disponivel_revenda ?? 'NULL') . "\n";
}

// 3. Check Reseller Logic
echo "Is Default Config? " . (ResellerBranding::isDefault() ? 'YES' : 'NO') . "\n";
