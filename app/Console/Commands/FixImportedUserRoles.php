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
        // Encontra usuários que são Admin (1) E têm perfil pendente (marca da importação)
        // Ignora admin principal por segurança
        $users = User::where('acesso', 1)
            ->where('pending_profile_completion', true)
            ->where('email', '!=', 'admin@adassoft.com')
            ->get();

        $count = $users->count();

        if ($count === 0) {
            $this->info("Nenhum usuário importado com nível Admin encontrado.");
            return;
        }

        if ($this->confirm("Encontrados $count usuários importados como Admin. Deseja rebaixá-los para Cliente?")) {
            User::where('acesso', 1)
                ->where('pending_profile_completion', true)
                ->where('email', '!=', 'admin@adassoft.com')
                ->update(['acesso' => 0]);

            $this->info("Sucesso! $count usuários foram corrigidos.");
        }
    }
}
