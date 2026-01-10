<?php

namespace App\Console\Commands;

use App\Models\Software;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportLegacySoftwares extends Command
{
    protected $signature = 'import:legacy-softwares {file}';
    protected $description = 'Importa softwares do SQL legado (tabela ss_products)';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("Arquivo não encontrado: $filePath");
            return;
        }

        $this->info("Iniciando importação de softwares...");

        $handle = fopen($filePath, "r");
        if (!$handle)
            return;

        $count = 0;
        $skipped = 0;

        while (($line = fgets($handle)) !== false) {
            // INSERT INTO `ss_products` ...
            // Detecta início do INSERT
            if (strpos($line, 'INSERT INTO `ss_products`') !== false) {
                $buffer = $line;
                // Continua lendo até encontrar ponto e vírgula
                while (strpos($buffer, ';') === false && ($nextLine = fgets($handle)) !== false) {
                    $buffer .= $nextLine;
                }

                $valuesPart = strstr($buffer, 'VALUES');
                $valuesPart = substr($valuesPart, 6);
                // Remove o ; final e espaços
                $valuesPart = trim($valuesPart, " ;\r\n");

                // Regex ajustado para lidar com , dentro de strings '...'
                // Padrão que tenta respeitar aspas simples escapadas
                // Se falhar, usaremos um parser mais simples linha a linha se o arquivo for bem formatado

                // Explode by "),(" que é o separador padrão de dumps MySQL
                // Mas cuidado com ")" dentro de strings.
                // Melhor abordagem simples: separar manualmente

                $rows = preg_split('/\),\s*\(/', $valuesPart);

                foreach ($rows as $row) {
                    $row = trim($row, "()");
                    try {
                        $this->processProduct($row);
                        $count++;
                    } catch (\Exception $e) {
                        // $this->error("Erro: " . $e->getMessage());
                        $skipped++;
                    }
                }
            }
        }

        fclose($handle);
        $this->info("Softwares importados: $count. Ignorados/Erro: $skipped.");
        $this->info("IMPORTANTE: Se as licenças já foram importadas usando esses IDs, execute agora 'php artisan import:legacy-plans' (se houver) ou crie os planos manualmente.");
    }

    private function processProduct($rawCsv)
    {
        // CREATE TABLE `ss_products` (
        //   `ID` int UNSIGNED NOT NULL, [0]
        //   `DISABLED` int UNSIGNED NOT NULL DEFAULT '0', [1]
        //   `NAME` varchar(80) ..., [2]
        //   `ICON` [3], `IMAGE` [4], `DISCOUNTS` [5], `FREEBUTTON` [6], 
        //   `BUYBUTTON` [7], `MOREBUTTON` [8], `URLMORE` [9], `DESCRIP` [10],
        //   `RSUBJECT` [11], `USUBJECT` [12], `VERSION` [13], ...

        $data = str_getcsv($rawCsv, ",", "'");

        $id = (int) $data[0];
        $disabled = (int) $data[1];
        $name = trim($data[2] ?? 'Software Desconhecido');
        $version = trim($data[13] ?? '1.0');
        $desc = trim($data[10] ?? '');

        // Verifica se já existe software com este ID (para manter integridade referencial com licenças importadas)
        // Como o ID no banco é auto-increment, vamos TENTAR forçar o ID inserindo manualmente ou buscando por nome.
        // Se usar Model::create, o ID é ignorado a menos que 'incrementing' = false.
        // Vamos checar se existe pelo nome primeiro.

        $exists = Software::find($id);
        if ($exists) {
            $this->info("Software ID $id ($name) já existe. Pulando.");
            return;
        }

        // Para forçar ID, podemos usar uma instância nova e salvar
        $soft = new Software();
        $soft->id = $id; // IMPORTANTE: Manter o ID antigo para bater com IDPRO da licença
        $soft->codigo = 'SW-OLD-' . $id;
        $soft->nome_software = $name;
        $soft->slug = Str::slug($name) . '-' . $id;
        $soft->versao = $version ?: '1.0';
        $soft->descricao = $desc;
        $soft->status = ($disabled == 0); // 0=Ativo
        $soft->plataforma = 'desktop'; // Default

        // Gerar API Key (obrigatória)
        $apiKey = 'sk_' . bin2hex(random_bytes(16));
        $soft->api_key_hash = hash('sha256', $apiKey);
        $soft->api_key_hint = substr($apiKey, -6);
        $soft->api_key_gerada_em = now();
        $soft->data_cadastro = now();

        $soft->save();
        // $this->info("Importado: $name (ID: $id)");
    }
}
