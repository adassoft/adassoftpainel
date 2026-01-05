<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Company;

// Pega o primeiro usuário admin/revenda ou pelo ID se souber (vamos listar os prováveis)
echo "=== DIAGNÓSTICO DE VÍNCULO REVENDA ===\n";

$users = User::whereIn('acesso', [1, 2])->get();

foreach ($users as $user) {
    echo "\n------------------------------------------------\n";
    echo "Usuário: {$user->nome} (ID: {$user->id}, Login: {$user->login})\n";
    echo "CNPJ User (Raw): '{$user->cnpj}'\n";

    $cleanCnpj = preg_replace('/\D/', '', $user->cnpj);
    echo "CNPJ User (Clean): '{$cleanCnpj}'\n";

    echo "Empresa ID vinculado: " . ($user->empresa_id ?? 'NULL') . "\n";

    // Tenta carregar via relacionamento ID
    $empresaViaId = $user->empresa;
    if ($empresaViaId) {
        echo "[OK] Empresa carregada via ID.\n";
        echo "     Razão: {$empresaViaId->razao}\n";
        echo "     CNPJ Empresa: '{$empresaViaId->cnpj}'\n";
        echo "     Token Asaas: " . substr($empresaViaId->asaas_access_token ?? '', 0, 10) . "...\n";
    } else {
        echo "[FALHA] Não foi possível carregar empresa via ID.\n";
    }

    // Tenta carregar via CNPJ
    $empresaViaCnpj = Company::where('cnpj', $cleanCnpj)->first();
    if ($empresaViaCnpj) {
        echo "[INFO] Empresa encontrada via busca de CNPJ '{$cleanCnpj}'.\n";
        echo "       ID da Empresa:v{$empresaViaCnpj->codigo}\n";
        echo "       Diferença de ID? " . ($user->empresa_id != $empresaViaCnpj->codigo ? "SIM (Vínculo Errado ou Ausente)" : "NÃO") . "\n";
    } else {
        echo "[ALERTA] Nenhuma empresa encontrada com o CNPJ '{$cleanCnpj}' na tabela 'empresa'.\n";

        // Tenta achar empresa 'suja'
        $empresaSuja = Company::where('cnpj', 'like', "%{$cleanCnpj}%")->first();
        if ($empresaSuja) {
            echo "[PISTA] Achei uma empresa parecida (ID {$empresaSuja->codigo}) com CNPJ '{$empresaSuja->cnpj}'. Formatação diferente?\n";
        }
    }
}
echo "\n------------------------------------------------\n";
