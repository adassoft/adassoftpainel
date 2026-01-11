<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;

class FixCompanyAddresses extends Command
{
    protected $signature = 'fix:company-addresses {file}';
    protected $description = 'Atualiza endereços (Cidade/UF) das empresas baseando-se no SQL legado de usuários';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("Arquivo não encontrado: $filePath");
            return;
        }

        $this->info("Lendo arquivo para atualizar endereços...");

        $handle = fopen($filePath, "r");
        if (!$handle) {
            $this->error("Erro ao abrir arquivo.");
            return;
        }

        $count = 0;
        $updated = 0;

        while (($line = fgets($handle)) !== false) {
            if (strpos($line, 'INSERT INTO `ss_users`') !== false) {
                $buffer = $line;
                while (strpos($buffer, ';') === false && ($nextLine = fgets($handle)) !== false) {
                    $buffer .= $nextLine;
                }

                $valuesPart = strstr($buffer, 'VALUES');
                $valuesPart = substr($valuesPart, 6);
                $valuesPart = trim($valuesPart, " ;\r\n");

                $rows = preg_split('/\),\s*\(/', $valuesPart);

                foreach ($rows as $row) {
                    $row = trim($row, "()");
                    try {
                        // 0: ID, 1: DISABLED, 2: FIRSTNAME, 3: LASTNAME, 4: COMPANY, 5: EMAIL, 
                        // 6: PHONE, 7: STREET, 8: CITY, 9: ZIP, 10: STAT, 11: COUNTRY, ...
                        $data = str_getcsv($row, ",", "'");

                        $email = trim($data[5] ?? '');
                        $cidade = trim($data[8] ?? '');
                        $uf = trim($data[10] ?? '');

                        if (empty($email))
                            continue;
                        if (empty($cidade) && empty($uf))
                            continue;

                        // Busca empresa pelo email
                        $company = Company::where('email', $email)->first();

                        // Se não achar por email, tenta via User -> Empresa
                        if (!$company) {
                            $user = \App\Models\User::where('email', $email)->first();
                            if ($user && $user->empresa_id) {
                                $company = Company::where('codigo', $user->empresa_id)->first();
                            }
                        }

                        if ($company) {
                            // Atualiza se estiver vazio
                            $updateData = [];
                            if (empty($company->cidade) && !empty($cidade)) {
                                $updateData['cidade'] = $cidade;
                            }
                            if (empty($company->uf) && !empty($uf)) {
                                $updateData['uf'] = substr($uf, 0, 2); // Garante 2 chars
                            }

                            if (!empty($updateData)) {
                                $company->update($updateData);
                                $updated++;
                                $this->info("Atualizado: " . $company->razao . " -> $cidade/$uf");
                            }
                        }

                        $count++;

                    } catch (\Exception $e) {
                        // ignore error
                    }
                }
            }
        }

        fclose($handle);
        $this->info("Processamento concluído. Registros lidos: $count. Empresas atualizadas: $updated.");
    }
}
