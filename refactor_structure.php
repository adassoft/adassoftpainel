<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Company;

echo "=== INICIANDO REFATORAÇÃO ESTRUTURAL (User -> Empresa ID) ===\n\n";

// 1. Adicionar coluna empresa_id se não existir
if (!Schema::hasColumn('usuario', 'empresa_id')) {
    echo "[1/3] Criando coluna 'empresa_id' na tabela 'usuario'...\n";
    Schema::table('usuario', function (Blueprint $table) {
        // Usar integer ou bigInteger dependendo da chave de empresa. 
        // Company model diz: protected $primaryKey = 'codigo'; normalmente auto-increment int.
        $table->integer('empresa_id')->nullable()->after('id')->index();
    });
    echo "      -> Coluna criada.\n";
} else {
    echo "[1/3] Coluna 'empresa_id' já existe.\n";
}

// 2. Migrar Dados (Vincular por CNPJ)
echo "[2/3] Vinculando usuários às empresas via CNPJ...\n";
$users = User::all();
$updated = 0;
$created = 0;

foreach ($users as $user) {
    // Se já tem vínculo, pula (ou força update se quiser garantir)
    if ($user->empresa_id)
        continue;

    $cnpj = preg_replace('/\D/', '', $user->cnpj);

    if (empty($cnpj))
        continue;

    // Busca Empresa
    $empresa = Company::where('cnpj', $cnpj)->first();

    if ($empresa) {
        $user->empresa_id = $empresa->codigo;
        $user->saveQuietly(); // Evita triggers do Laravel
        $updated++;
        echo "      -> User {$user->login} vinculado à Empresa ID {$empresa->codigo}\n";
    } else {
        // Empresa não existe! Vamos criar?
        // Sim, para garantir integridade.
        echo "      -> User {$user->login} (CNPJ {$cnpj}) não tem empresa. Criando...\n";

        $novaEmpresa = new Company();
        $novaEmpresa->cnpj = $cnpj;
        $novaEmpresa->razao = $user->nome ?? 'Empresa Sem Nome';
        $novaEmpresa->email = $user->email;
        $novaEmpresa->status = 'Ativo';
        $novaEmpresa->data = now();
        $novaEmpresa->save();

        $user->empresa_id = $novaEmpresa->codigo;
        $user->saveQuietly();
        $created++;
    }
}

echo "      -> Vínculos existentes atualizados: $updated\n";
echo "      -> Novas empresas criadas: $created\n";

// 3. Opcional: Adicionar FK constraint (se o banco for InnoDB e suportar)
// Schema::table('usuario', function (Blueprint $table) {
//     $table->foreign('empresa_id')->references('codigo')->on('empresa');
// });

echo "\n[3/3] Concluído. Agora atualize os Models e o Filament Resource!\n";
