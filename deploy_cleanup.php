<?php

// Script para limpeza de dados e cache em Produção
// Uso: php deploy_cleanup.php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== INICIANDO LIMPEZA DE DADOS (PRODUÇÃO) ===\n";

// 1. Limpar CNPJs na tabela User
echo "1. Normalizando CNPJs de Usuários...\n";
$users = \App\Models\User::whereNotNull('cnpj')->get();
$userCount = 0;
foreach ($users as $user) {
    // Se tem caracteres não numéricos
    if (preg_match('/\D/', $user->cnpj)) {
        $clean = preg_replace('/\D/', '', $user->cnpj);

        // Verifica se já existe um usuário com esse CNPJ limpo (pra evitar duplicidade de chave unique)
        // Se existir duplicidade, teremos um problema. Vamos assumir que não há colisão user/user.

        // Atualiza direto via DB para evitar validações do Model que podem travar
        \Illuminate\Support\Facades\DB::table('usuario')
            ->where('id', $user->id)
            ->update(['cnpj' => $clean]);

        $userCount++;
    }
}
echo "   -> $userCount usuários corrigidos.\n";

// 2. Limpar CNPJs na tabela Empresa
echo "2. Normalizando CNPJs de Empresas...\n";
$empresas = \App\Models\Company::all(); // Company table = empresa
$empCount = 0;
foreach ($empresas as $emp) {
    if (preg_match('/\D/', $emp->cnpj)) {
        $clean = preg_replace('/\D/', '', $emp->cnpj);

        \Illuminate\Support\Facades\DB::table('empresa')
            ->where('codigo', $emp->codigo)
            ->update(['cnpj' => $clean]);

        $empCount++;
    }

    // Aproveita para limpar CNPJ Representante se tiver
    if (!empty($emp->cnpj_representante) && preg_match('/\D/', $emp->cnpj_representante)) {
        $cleanRep = preg_replace('/\D/', '', $emp->cnpj_representante);
        \Illuminate\Support\Facades\DB::table('empresa')
            ->where('codigo', $emp->codigo)
            ->update(['cnpj_representante' => $cleanRep]);
    }
}
echo "   -> $empCount empresas corrigidas.\n";

// 3. Limpar Cache do Laravel
echo "3. Limpando Cache do Sistema...\n";
\Illuminate\Support\Facades\Artisan::call('optimize:clear');
echo "   -> Cache limpo.\n";

echo "=== CONCLUÍDO COM SUCESSO ===\n";
echo "Agora você pode acessar o Painel Admin e configurar o Token Asaas da Revenda Padrão.\n";
