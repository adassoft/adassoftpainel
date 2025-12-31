<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Empresa;
use App\Models\ResellerConfig;
use Illuminate\Support\Facades\DB;

class ResellersSeeder extends Seeder
{
    public function run()
    {
        // 1. Revenda Principal: ADASSOFT
        $admin = User::firstOrCreate(
            ['email' => 'admin@adassoft.com.br'],
            [
                'nome' => 'Adassoft Admin',
                'senha' => Hash::make('password'),
                'cnpj' => '00000000000100',
                'acesso' => 1, // 1 = Admin
                'login' => 'admin',
                'data' => now(),
                'status' => 'Ativo',
                'uf' => 'SP'
            ]
        );

        // Empresa do Admin (Relation via User)
        $admin->empresa()->updateOrCreate(
            ['cnpj' => $admin->cnpj],
            [
                'razao' => 'Adassoft Tecnologia',
                'endereco' => 'Rua Principal, 100',
                'cidade' => 'São Paulo',
                'bairro' => 'Centro',
                'uf' => 'SP',
                'fone' => '11999999999',
                'email' => 'admin@adassoft.com.br',
                'asaas_access_token' => 'token_simulado_adassoft_123',
                'data' => now(),
                'status' => 'Ativo',
                'saldo' => 0
            ]
        );

        // Config White Label do Admin
        ResellerConfig::updateOrCreate(
            ['usuario_id' => $admin->id],
            [
                'nome_sistema' => 'Adassoft Store',
                'slogan' => 'Tecnologia que impulsiona',
                'dominios' => 'localhost,127.0.0.1,adassoft.test',
                'cor_primaria_gradient_start' => '#4e73df',
                'cor_primaria_gradient_end' => '#224abe',
                'ativo' => true,
                'status_aprovacao' => 'aprovado'
            ]
        );

        // 2. Revenda Parceira: SOFTHOUSE X
        $parceiro = User::firstOrCreate(
            ['email' => 'contato@softhousex.com.br'],
            [
                'nome' => 'Dono Softhouse X',
                'senha' => Hash::make('password'),
                'cnpj' => '11111111000111',
                'acesso' => 2, // 2 = Revenda
                'login' => 'softhousex',
                'data' => now(),
                'status' => 'Ativo',
                'uf' => 'RJ'
            ]
        );
        $parceiro->empresa()->updateOrCreate(
            ['cnpj' => $parceiro->cnpj],
            [
                'razao' => 'Softhouse X Ltda',
                'endereco' => 'Av. Comercial, 200',
                'cidade' => 'Rio de Janeiro',
                'bairro' => 'Centro',
                'uf' => 'RJ',
                'fone' => '11988887777',
                'email' => 'contato@softhousex.com.br',
                'asaas_access_token' => '',
                'data' => now(),
                'status' => 'Ativo',
                'saldo' => 0
            ]
        );

        // Config White Label do Parceiro
        ResellerConfig::updateOrCreate(
            ['usuario_id' => $parceiro->id],
            [
                'nome_sistema' => 'SoftX Sistemas',
                'slogan' => 'Soluções para o seu varejo',
                'dominios' => 'parceiro.test',
                'cor_primaria_gradient_start' => '#1cc88a', // Verde
                'cor_primaria_gradient_end' => '#13855c',
                'ativo' => true,
                'status_aprovacao' => 'aprovado'
            ]
        );

        $this->command->info('Revendas configuradas com sucesso!');
        $this->command->info('Admin (com pagamento): admin@adassoft.com.br / password');
        $this->command->info('Parceiro (sem pagamento): contato@softhousex.com.br / password');
    }
}
