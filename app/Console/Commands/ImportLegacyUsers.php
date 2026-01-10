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
                $buffer = $line;
                while (strpos($buffer, ';') === false && ($nextLine = fgets($handle)) !== false) {
                    $buffer .= $nextLine;
                }

                $valuesPart = strstr($buffer, 'VALUES');
                $valuesPart = substr($valuesPart, 6); // Remove 'VALUES'
                $valuesPart = trim($valuesPart, " ;\r\n");

                $rows = preg_split('/\),\s*\(/', $valuesPart);

                foreach ($rows as $row) {
                    $row = trim($row, "()");
                    try {
                        $this->processUser($row);
                        $count++;
                    } catch (\Exception $e) {
                        // $this->error("Erro: " . $e->getMessage());
                        $skipped++;
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
            'password' => Hash::make(Str::random(16)), // Senha aleatória
            'celular' => $phone,
            'pending_profile_completion' => true,
            'acesso' => 3, // 3 = Cliente (Conforme informado)
            'status' => 'Ativo',
            // Poderíamos salvar outros dados em JSON se necessário
        ]);

        $this->info("Importado: $email");
    }
}
