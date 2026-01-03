<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use App\Models\Software;

class DebugSoftwareReseller extends Command
{
    protected $signature = 'debug:software-reseller';
    protected $description = 'Debug software reseller visibility';

    public function handle()
    {
        $this->info("Checking 'softwares' table...");

        $hasColumn = Schema::hasColumn('softwares', 'disponivel_revenda');
        $this->info("Column 'disponivel_revenda' exists? " . ($hasColumn ? 'YES' : 'NO'));

        if ($hasColumn) {
            $total = Software::count();
            $enabled = Software::where('disponivel_revenda', true)->count();
            $disabled = Software::where('disponivel_revenda', false)->count();
            $nulls = Software::whereNull('disponivel_revenda')->count();

            $this->info("Total Softwares: $total");
            $this->info("Enabled for Reseller (1): $enabled");
            $this->info("Disabled for Reseller (0): $disabled");
            $this->info("NULLs: $nulls");

            $first = Software::first();
            if ($first) {
                $this->info("Sample ID {$first->id}: " . $first->disponivel_revenda);
            }
        }
    }
}
