<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Migração problemática esvaziada para destravar a fila.
        // As correções reais foram movidas para:
        // 2026_01_03_060118_fix_user_library_fake_key_issue.php
    }

    public function down(): void
    {
        // Nada a reverter
    }
};
