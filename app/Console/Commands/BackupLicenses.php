<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackupLicenses extends Command
{
    protected $signature = 'backup:licenses';
    protected $description = 'Realiza backup em SQL das tabelas críticas de licenciamento';

    public function handle()
    {
        // Tabelas para backup
        $tables = ['historico_seriais', 'licencas_ativas', 'terminais_software', 'api_keys'];
        // Tente incluir log se existir, mas não falhe se não existir
        try {
            if (DB::getSchemaBuilder()->hasTable('log_validacoes')) {
                $tables[] = 'log_validacoes';
            }
        } catch (\Exception $e) {
        }

        $timestamp = date('Ymd_His');
        $filename = "backup_licencas_{$timestamp}.sql";

        // Salva em storage/app/backups
        $directory = storage_path("app/backups");
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        $path = "{$directory}/{$filename}";

        $this->info("Iniciando backup de tabelas críticas...");

        $handle = fopen($path, 'w');
        fwrite($handle, "-- Backup AdasSoft Licencas: {$timestamp}\n\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        foreach ($tables as $table) {
            $this->info("Processando tabela: {$table}...");

            try {
                // Estrutura
                $createRef = DB::select("SHOW CREATE TABLE {$table}");
                // O nome da prop pode variar dependendo do driver PDO, geralmente é 'Create Table'
                $createSql = $createRef[0]->{'Create Table'} ?? $createRef[0]->{'create table'} ?? null;

                if ($createSql) {
                    fwrite($handle, "-- Estrutura da tabela `{$table}`\n");
                    fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
                    fwrite($handle, $createSql . ";\n\n");
                }

                // Dados
                fwrite($handle, "-- Dados da tabela `{$table}`\n");

                // Processar em chunks para não estourar memória
                DB::table($table)->orderBy('id')->chunk(500, function ($rows) use ($handle, $table) {
                    foreach ($rows as $row) {
                        $rowArray = (array) $row;
                        $values = array_map(function ($value) {
                            if (is_null($value))
                                return "NULL";
                            // Escape simples sql
                            $escaped = addslashes($value);
                            // Corrige quebra de linha para SQL
                            $escaped = str_replace(["\r", "\n"], ['\r', '\n'], $escaped);
                            return "'{$escaped}'";
                        }, $rowArray);

                        $sql = "INSERT INTO `{$table}` VALUES (" . implode(", ", $values) . ");\n";
                        fwrite($handle, $sql);
                    }
                });
                fwrite($handle, "\n");

            } catch (\Exception $e) {
                $this->error("Erro ao processar tabela {$table}: " . $e->getMessage());
                fwrite($handle, "-- ERRO ao exportar tabela {$table}: " . $e->getMessage() . "\n\n");
            }
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);

        $this->info("Backup salvo em: {$path}");
    }
}
