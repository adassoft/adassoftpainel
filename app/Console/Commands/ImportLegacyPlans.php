<?php

namespace App\Console\Commands;

use App\Models\Plano;
use App\Models\Software;
use Illuminate\Console\Command;

class ImportLegacyPlans extends Command
{
    protected $signature = 'import:legacy-plans {file}';
    protected $description = 'Importa planos (editions) do SQL legado (tabela ss_editions)';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("Arquivo não encontrado: $filePath");
            return;
        }

        $this->info("Iniciando importação de planos...");

        $handle = fopen($filePath, "r");
        if (!$handle)
            return;

        $count = 0;
        $skipped = 0;

        while (($line = fgets($handle)) !== false) {
            // INSERT INTO `ss_editions` ...
            if (strpos($line, 'INSERT INTO `ss_editions`') !== false) {
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
                        $this->processPlan($row);
                        $count++;
                    } catch (\Exception $e) {
                        // $this->error("Erro: " . $e->getMessage());
                        $skipped++;
                    }
                }
            }
        }

        fclose($handle);
        $this->info("Planos importados: $count. Ignorados/Erro: $skipped.");
    }

    private function processPlan($rawCsv)
    {
        // 0: ID, 1: IDPRO, 2: PRICE, 3: DAYS, 4: MACHINES, 5: DISABLED, ..., 
        // 9: NAME, ...

        $data = str_getcsv($rawCsv, ",", "'");

        $id = (int) $data[0];
        $softwareId = (int) $data[1];
        $priceCents = (int) $data[2];
        $days = (int) $data[3];
        $disabled = (int) ($data[5] ?? 0);
        $name = trim($data[9] ?? "Plano $id");

        // Verify software exists
        if (!Software::find($softwareId)) {
            // Log::warning("Plano ref software $softwareId (não encontrado).");
            // return; 
            // Better to skip or create orphan? Skip.
            throw new \Exception("Software $softwareId not found");
        }

        // Logic for recurrence (Days to Months)
        if ($days >= 360)
            $recorrencia = 12;
        elseif ($days >= 180)
            $recorrencia = 6;
        elseif ($days >= 90)
            $recorrencia = 3;
        else
            $recorrencia = 1; // Default 1 month

        // Logic for value
        $valor = $priceCents / 100;

        // Check exists
        if (Plano::find($id)) {
            // Already exists
            return;
        }

        $plano = new Plano();
        $plano->id = $id; // Keep legacy ID
        $plano->software_id = $softwareId;
        $plano->nome_plano = $name;
        $plano->valor = $valor;
        $plano->recorrencia = $recorrencia;
        $plano->status = ($disabled == 0);
        $plano->data_cadastro = now();
        $plano->save();
    }
}
