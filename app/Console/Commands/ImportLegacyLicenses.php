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
                        $this->error("Erro na importação: " . $e->getMessage());
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

        $email = strtolower(trim($data[12] ?? '')); // EMAIL is index 12 based on the CREATE TABLE order
        if (empty($email))
            return;

        $user = User::where('email', $email)->first();
        if (!$user) {
            // Se o usuário não existe, talvez não tenha sido importado ainda?
            // Podemos pular ou criar um user placeholder? Melhor pular.
            return;
        }

        // Garante que usuario tenha empresa
        if (!$user->empresa_id) {
            $razao = substr($user->name, 0, 48) . ' (Importado)';
            $company = Company::create([
                'codigo' => Company::max('codigo') + 1,
                'razao' => $razao,
                'email' => $email,
                'fone1' => $user->celular ?? '',
            ]);
            $user->empresa_id = $company->codigo;
            $user->save();
        }

        $softwareId = (int) ($data[3] ?? 0); // IDPRO
        $machines = (int) ($data[4] ?? 1); // MACHINES
        $startDate = $this->parseDate($data[15] ?? null);
        $endDate = $this->parseDate($data[16] ?? null);
        $isActive = ($data[5] ?? '0') == '0';
        $status = $isActive ? 'ativo' : 'suspenso';

        // Verifica se já existe para não duplicar
        $exists = License::where('empresa_codigo', $user->empresa_id)
            ->where('software_id', $softwareId)
            ->exists();

        if ($exists)
            return;

        // Busca CNPJ da Revenda Padrão
        $defaultResellerCnpj = '04733736000120';

        // Busca Software para gerar Serial correto
        $software = \App\Models\Software::find($softwareId);
        $serial = null; // init

        if ($software) {
            try {
                $company = \App\Models\Company::where('codigo', $user->empresa_id)->first();
                if ($company) {
                    $licenseService = new \App\Services\LicenseService();
                    $serial = $licenseService->generateSerial($company, $software);
                }
            } catch (\Exception $e) {
                // Fallback se falhar
            }
        }

        if (!$serial) {
            $serial = strtoupper(Str::random(20));
        }

        $license = License::create([
            'empresa_codigo' => $user->empresa_id,
            'cnpj_revenda' => $defaultResellerCnpj,
            'software_id' => $softwareId,
            'terminais_permitidos' => $machines,
            'terminais_utilizados' => 0,
            'data_criacao' => $startDate,
            'data_ativacao' => $startDate,
            'data_expiracao' => $endDate,
            'status' => $status,
            'serial_atual' => $serial,
            'observacoes' => 'Importado do Legado',
        ]);

        // Registrar no Histórico
        \App\Models\SerialHistory::firstOrCreate(
            ['serial_gerado' => $serial],
            [
                'empresa_codigo' => $user->empresa_id,
                'software_id' => $softwareId,
                'data_geracao' => $startDate,
                'validade_licenca' => $endDate,
                'terminais_permitidos' => $machines,
                'ativo' => $isActive,
                'observacoes' => json_encode(['origem' => 'importacao_legado']),
            ]
        );
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
