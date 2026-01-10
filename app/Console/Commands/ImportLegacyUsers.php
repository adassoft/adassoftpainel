<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImportLegacyUsers extends Command
{
    protected $signature = 'import:legacy-users {file}';
    protected $description = 'Importa usuários do SQL legado (tabela ss_users)';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("Arquivo não encontrado: $filePath");
            return;
        }

        $this->info("Iniciando importação de: $filePath");

        $handle = fopen($filePath, "r");
        if (!$handle) {
            $this->error("Erro ao abrir arquivo.");
            return;
        }

        $count = 0;
        $skipped = 0;

        while (($line = fgets($handle)) !== false) {
            // Procura por linhas de INSERT INTO `ss_users`
            if (strpos($line, 'INSERT INTO `ss_users`') !== false) {
                // Remove o prefixo do INSERT para ficar só com os VALUES
                // Ex: ... VALUES (1, 0, 'Nome', ...), (2, 0, ...)
                $valuesPart = strstr($line, 'VALUES');
                $valuesPart = substr($valuesPart, 6); // Remove 'VALUES'
                $valuesPart = trim($valuesPart, " ;\r\n");

                // Parseia os grupos (...)
                // Regex simples para capturar conteudos entre parênteses
                // Atenção: F falha se houver parenteses dentro do texto, mas para esse dump simples deve servir
                if (preg_match_all('/\((.*?)\)/', $valuesPart, $matches)) {
                    foreach ($matches[1] as $userDataRaw) {
                        try {
                            $this->processUser($userDataRaw);
                            $count++;
                        } catch (\Exception $e) {
                            $this->error("Erro ao processar linha: " . $e->getMessage());
                            $skipped++;
                        }
                    }
                }
            }
        }

        fclose($handle);
        $this->info("Importação concluída! Importados: $count. Pulados: $skipped.");
    }

    private function processUser($rawCsv)
    {
        // O rawCsv vem como: 1, 0, 'Bruno', 'Silva', ...
        // Precisamos tratar as aspas e separar por vírgula respeitando strings
        $data = str_getcsv($rawCsv, ",", "'");

        // Estrutura mapeada do SQL lido anteriormente:
        // 0: ID, 1: DISABLED, 2: FIRSTNAME, 3: LASTNAME, 4: COMPANY, 5: EMAIL, 
        // 6: PHONE, 7: STREET, 8: CITY, 9: ZIP, 10: STAT, 11: COUNTRY, 12: COMMENT, ...

        $email = trim($data[5] ?? '');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return; // Pula sem email válido
        }

        $exists = User::where('email', $email)->exists();
        if ($exists) {
            $this->warn("Email já existe: $email");
            return;
        }

        $firstName = $data[2] ?? '';
        $lastName = $data[3] ?? '';
        $fullName = trim("$firstName $lastName");
        if (empty($fullName))
            $fullName = 'Usuário Importado';

        $phone = $data[6] ?? null; // PHONE

        // Criação do usuário
        User::create([
            'name' => $fullName,
            'email' => $email,
            'password' => Hash::make(Str::random(16)), // Senha aleatória, obriga recuperação
            'celular' => $phone,
            'pending_profile_completion' => true,
            // Poderíamos salvar outros dados em JSON se necessário
        ]);

        $this->info("Importado: $email");
    }
}
