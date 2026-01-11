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

        $phone = $data[6] ?? null; // PHONE
        $cidade = trim($data[8] ?? ''); // CITY
        $uf = trim($data[10] ?? ''); // STAT (State)

        $firstName = $data[2] ?? '';
        $lastName = $data[3] ?? '';
        $fullName = trim("$firstName $lastName");
        if (empty($fullName))
            $fullName = 'Usuário Importado';

        $user = User::where('email', $email)->first();

        if (!$user) {
            // Criação do usuário se não existir
            $user = User::create([
                'name' => $fullName,
                'email' => $email,
                'password' => Hash::make(Str::random(16)),
                'celular' => $phone,
                'pending_profile_completion' => true,
                'acesso' => 3,
                'status' => 'Ativo',
            ]);
            $this->info("Usuário criado: $email");
        } else {
            // Se existir, garantimos que os dados básicos estão atualizados se necessário?
            // Por enquanto focamos na empresa.
        }

        // Lógica de Empresa (Company)
        // O legado tinha endereço no usuário, mas no sistema novo fica na Empresa.
        // Vamos criar ou atualizar a empresa vinculada.

        $company = null;
        if ($user->empresa_id) {
            $company = \App\Models\Company::where('codigo', $user->empresa_id)->first();
        }

        // Se não achou pelo ID, tenta pelo email (caso tenha sido criado isolado)
        if (!$company) {
            $company = \App\Models\Company::where('email', $email)->first();
        }

        if (!$company) {
            // Cria nova empresa
            $razao = substr($user->name, 0, 48) . ' (Importado)';
            $company = \App\Models\Company::create([
                'codigo' => \App\Models\Company::max('codigo') + 1,
                'razao' => $razao,
                'email' => $email,
                'fone1' => $phone,
                'cidade' => $cidade,
                'uf' => substr($uf, 0, 2),
            ]);

            // Vincula ao usuário
            $user->empresa_id = $company->codigo;
            $user->save();
            $this->info("Empresa criada para: $email");
        } else {
            // Atualiza endereço se estiver vazio na empresa existente
            $updateData = [];
            if (empty($company->cidade) && !empty($cidade)) {
                $updateData['cidade'] = $cidade;
            }
            if (empty($company->uf) && !empty($uf)) {
                $updateData['uf'] = substr($uf, 0, 2);
            }

            if (!empty($updateData)) {
                $company->update($updateData);
                $this->info("Endereço atualizado para empresa de: $email");
            }
        }
    }
}
