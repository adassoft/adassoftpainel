<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\License;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportLegacyLicenses extends Command
{
    protected $signature = 'import:legacy-licenses {file}';
    protected $description = 'Importa licenças do SQL legado (tabela ss_subscriptions)';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("Arquivo não encontrado: $filePath");
            return;
        }

        $this->info("Iniciando importação de licenças...");

        $handle = fopen($filePath, "r");
        if (!$handle)
            return;

        $count = 0;
        $skipped = 0;

        while (($line = fgets($handle)) !== false) {
            if (strpos($line, 'INSERT INTO `ss_subscriptions`') !== false) {
                $buffer = $line;
                while (strpos($buffer, ';') === false && ($nextLine = fgets($handle)) !== false) {
                    $buffer .= $nextLine;
                }

                $valuesPart = strstr($buffer, 'VALUES');
                $valuesPart = substr($valuesPart, 6);
                $valuesPart = trim($valuesPart, " ;\r\n");

                // Split by ),( to handle multiple rows
                $rows = preg_split('/\),\s*\(/', $valuesPart);

                foreach ($rows as $row) {
                    $row = trim($row, "()");
                    try {
                        $this->processSubscription($row);
                        $count++;
                    } catch (\Exception $e) {
                        // $this->error("Erro: " . $e->getMessage());
                        $skipped++;
                    }
                }
            }
        }

        fclose($handle);
        $this->info("Licenças importadas: $count. Ignoradas/Erro: $skipped.");
    }

    private function processSubscription($rawCsv)
    {
        // 0: ID, 1: IDED, 2: IDUSERS, 3: IDPRO, 4: MACHINES, 5: DISABLED, ..., 
        // 12: EMAIL, 13: LICDATA, ..., 15: STARTDATE, 16: ENDDATE

        $data = str_getcsv($rawCsv, ",", "'");

        $email = trim($data[12] ?? ''); // EMAIL is index 12 based on the CREATE TABLE order
        if (empty($email))
            return;

        $user = User::where('email', $email)->first();
        if (!$user) {
            // Se o usuário não existe, talvez não tenha sido importado ainda?
            // Podemos pular ou criar um user placeholder? Melhor pular.
            return;
        }

        // Garante que usuario tenha empresa
        if (!$user->empresa_codigo) {
            $company = Company::create([
                'codigo' => Company::max('codigo') + 1,
                'razao' => $user->name . ' (Importado)',
                'email' => $email,
                'fone1' => $user->celular ?? '',
            ]);
            $user->empresa_codigo = $company->codigo;
            $user->save();
        }

        $softwareId = (int) ($data[3] ?? 0); // IDPRO
        $machines = (int) ($data[4] ?? 1); // MACHINES
        $startDate = $this->parseDate($data[15] ?? null);
        $endDate = $this->parseDate($data[16] ?? null);
        $status = ($data[5] ?? '0') == '0' ? 'ativo' : 'inativo'; // DISABLED=0 -> Ativo

        // Mapeamento de Status Legado se tiver outro campo
        // STATUS (idx 7) -> ?

        // Verifica se já existe para não duplicar
        $exists = License::where('empresa_codigo', $user->empresa_codigo)
            ->where('software_id', $softwareId)
            ->exists();

        if ($exists)
            return;

        // Busca CNPJ da Revenda Padrão (ID 5 conforme informado)
        // Se não achar, usa placeholder
        static $defaultResellerCnpj = null;
        if ($defaultResellerCnpj === null) {
            $reseller = User::find(5);
            $defaultResellerCnpj = $reseller ? $reseller->cnpj : '00000000000100';
        }

        License::create([
            'empresa_codigo' => $user->empresa_codigo,
            'cnpj_revenda' => $defaultResellerCnpj,
            'software_id' => $softwareId, // Assumindo IDPRO == software_id novo
            'terminais_permitidos' => $machines,
            'terminais_utilizados' => 0,
            'data_criacao' => $startDate,
            'data_ativacao' => $startDate,
            'data_expiracao' => $endDate,
            'status' => $status,
            'serial_atual' => Str::upper(Str::random(20)), // Gera um serial placeholder
            'observacoes' => 'Importado do Legado',
        ]);
    }

    private function parseDate($dateStr)
    {
        if (!$dateStr || $dateStr === 'NULL')
            return now();
        try {
            return Carbon::parse($dateStr);
        } catch (\Exception $e) {
            return now();
        }
    }
}
