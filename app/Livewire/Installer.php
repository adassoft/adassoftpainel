<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\Installer\EnvironmentManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Filament\Models\Contracts\FilamentUser;

class Installer extends Component
{
    public int $step = 1;
    public $requirements = [];

    // Step 2: Database Config
    public $app_name = 'AdasSoft';
    public $app_url = '';
    public $db_host = '127.0.0.1';
    public $db_port = '3306';
    public $db_database = 'adassoft';
    public $db_username = 'root';
    public $db_password = '';

    // Step 4: Admin User
    public $admin_name = 'Administrador';
    public $admin_email = 'admin@admin.com';
    public $admin_password = '';
    public $admin_password_confirmation = '';

    public $connectionStatus = null;
    public $connectionMsg = '';
    public $installationOutput = [];

    public function mount()
    {
        $this->app_url = url('/');
        $checker = new EnvironmentManager();
        $this->requirements = $checker->checkRequirements();
    }

    public function nextStep()
    {
        // Validação Passo 2 (Ambiente)
        if ($this->step === 2) {
            $this->validate([
                'app_name' => 'required',
                'db_host' => 'required',
                'db_database' => 'required',
                'db_username' => 'required',
            ]);

            if (!$this->testDatabaseConnection()) {
                return;
            }

            $this->saveEnv();
        }

        $this->step++;
    }

    public function testDatabaseConnection()
    {
        $this->connectionStatus = null;
        $this->connectionMsg = '';

        try {
            // Tenta conexão temporária
            $config = config('database.connections.mysql');
            $config['host'] = $this->db_host;
            $config['port'] = $this->db_port;
            $config['database'] = $this->db_database;
            $config['username'] = $this->db_username;
            $config['password'] = $this->db_password;

            // Define uma conexão temporária em tempo de execução
            config(['database.connections.installer_temp' => $config]);

            // Tenta conectar
            DB::connection('installer_temp')->getPdo();

            $this->connectionStatus = true;
            $this->connectionMsg = 'Conexão bem sucedida!';
            return true;
        } catch (\Exception $e) {
            $this->connectionStatus = false;
            $this->connectionMsg = 'Erro: ' . $e->getMessage();
            return false;
        }
    }

    public function saveEnv()
    {
        $manager = new EnvironmentManager();
        $manager->updateEnv([
            'APP_NAME' => $this->app_name,
            'APP_URL' => $this->app_url,
            'DB_HOST' => $this->db_host,
            'DB_PORT' => $this->db_port,
            'DB_DATABASE' => $this->db_database,
            'DB_USERNAME' => $this->db_username,
            'DB_PASSWORD' => $this->db_password,
        ]);

        Artisan::call('config:clear');
    }

    public function runMigration()
    {
        try {
            // Força a usar as credenciais novas
            config([
                'database.connections.mysql.host' => $this->db_host,
                'database.connections.mysql.port' => $this->db_port,
                'database.connections.mysql.database' => $this->db_database,
                'database.connections.mysql.username' => $this->db_username,
                'database.connections.mysql.password' => $this->db_password,
            ]);
            DB::purge('mysql');

            Artisan::call('migrate:fresh', ['--force' => true]);
            $this->installationOutput[] = 'Banco de dados criado e tabelas migradas.';

            // Se necessário, rodar Seeders básicos
            // Artisan::call('db:seed', ['--force' => true]);

            // Gerar Key se não existir
            if (empty(env('APP_KEY'))) {
                Artisan::call('key:generate', ['--force' => true]);
                $this->installationOutput[] = 'APP_KEY Gerada.';
            }

            // Shield / Admin Panel setup
            // Artisan::call('shield:generate', ['--all' => true]);
            // $this->installationOutput[] = 'Permissões do painel configuradas.';

            $this->step++;
        } catch (\Exception $e) {
            $this->addError('migration', 'Erro Fatal na Migração: ' . $e->getMessage());
        }
    }

    public function createAdmin()
    {
        $this->validate([
            'admin_name' => 'required',
            'admin_email' => 'required|email',
            'admin_password' => 'required|confirmed|min:8',
        ]);

        try {
            $user = User::create([
                'name' => $this->admin_name,
                'email' => $this->admin_email,
                'password' => Hash::make($this->admin_password),
                'email_verified_at' => now(),
                'acesso' => 1, // 1 = Admin, conforme convenção do user
            ]);

            // Bloqueio de reinstalação
            file_put_contents(storage_path('installed'), 'Instalado em ' . now());

            return redirect()->to('/admin/login');
        } catch (\Exception $e) {
            $this->addError('admin', 'Erro ao criar admin: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.installer')->layout('components.layouts.installer');
    }
}
