<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Company;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Vincula empresas importadas à revenda padrão para que apareçam na lista
        Company::where('razao', 'like', '%(Importado)')
            ->whereNull('cnpj_representante')
            ->update(['cnpj_representante' => '04733736000120']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Irreversível com segurança
    }
};
