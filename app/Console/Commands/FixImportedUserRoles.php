<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class FixImportedUserRoles extends Command
{
    protected $signature = 'fix:demote-imported-admins';
    protected $description = 'Rebaixa usuários importados (que ficaram como Admin) para Clientes';

    public function handle()
    {
        // Encontra TODOS usuários que são Admin (1) exceto o admin principal
        $adminEmail = 'adassoft@outlook.com.br';

        $users = User::where('acesso', 1)
            ->where('email', '!=', $adminEmail)
            ->get();

        $count = $users->count();

        if ($count === 0) {
            $this->info("Nenhum usuário com nível Admin indevido encontrado.");
            return;
        }

        if ($this->confirm("Encontrados $count usuários com nível Admin (exceto $adminEmail). Deseja rebaixá-los para Cliente (Nível 3)?")) {
            User::where('acesso', 1)
                ->where('email', '!=', $adminEmail)
                ->update(['acesso' => 3]);

            $this->info("Sucesso! $count usuários foram corrigidos para o Nível 3.");
        }
    }
}
