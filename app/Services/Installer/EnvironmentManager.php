<?php

namespace App\Services\Installer;

use Illuminate\Support\Facades\File;

class EnvironmentManager
{
    /**
     * Atualiza o arquivo .env com os dados fornecidos.
     */
    public function updateEnv(array $data): string
    {
        $envPath = base_path('.env');

        // Se não existir .env, copia do .env.example
        if (!File::exists($envPath)) {
            if (File::exists(base_path('.env.example'))) {
                File::copy(base_path('.env.example'), $envPath);
            } else {
                File::put($envPath, '');
            }
        }

        $envContent = File::get($envPath);

        foreach ($data as $key => $value) {
            // Se o valor tiver espaços, coloca aspas
            if (str_contains($value, ' ')) {
                $value = '"' . $value . '"';
            }

            // Verifica se a chave já existe
            if (preg_match("/^{$key}=.*/m", $envContent)) {
                // Atualiza
                $envContent = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$value}",
                    $envContent
                );
            } else {
                // Adiciona no final
                $envContent .= "\n{$key}={$value}";
            }
        }

        File::put($envPath, $envContent);

        return $envContent;
    }

    /**
     * Verifica requisitos mínimos do servidor
     */
    public function checkRequirements(): array
    {
        $requirements = [
            'php' => [
                'min_version' => '8.1.0',
                'current' => phpversion(),
                'status' => version_compare(phpversion(), '8.1.0', '>='),
            ],
            'extensions' => [
                'bcmath' => extension_loaded('bcmath'),
                'ctype' => extension_loaded('ctype'),
                'json' => extension_loaded('json'),
                'mbstring' => extension_loaded('mbstring'),
                'openssl' => extension_loaded('openssl'),
                'pdo' => extension_loaded('pdo'),
                'tokenizer' => extension_loaded('tokenizer'),
                'xml' => extension_loaded('xml'),
                'curl' => extension_loaded('curl'),
                'fileinfo' => extension_loaded('fileinfo'),
                'gd' => extension_loaded('gd'),
            ],
            'permissions' => [
                'storage/app' => is_writable(storage_path('app')),
                'storage/framework' => is_writable(storage_path('framework')),
                'storage/logs' => is_writable(storage_path('logs')),
                'bootstrap/cache' => is_writable(base_path('bootstrap/cache')),
            ]
        ];

        return $requirements;
    }
}
