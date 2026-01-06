<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Download;
use App\Models\DownloadVersion;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class SecureMigrateDownloads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'downloads:secure-migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move arquivos de download do disco público para o disco privado de produtos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando migração de arquivos para armazenamento seguro...');

        // 1. Migrar Versões (DownloadVersion) - Onde a maioria dos arquivos reais vive hoje
        $versions = DownloadVersion::all();
        $this->info("Verificando {$versions->count()} versões...");

        foreach ($versions as $version) {
            $this->migrateFile($version->arquivo_path, 'Versão #' . $version->id);
        }

        // 2. Migrar Downloads Legados (Download Model direto)
        $downloads = Download::whereNotNull('arquivo_path')->get();
        $this->info("Verificando {$downloads->count()} downloads principais (legacy)...");

        foreach ($downloads as $download) {
            // Ignora se for URL externa
            if (filter_var($download->arquivo_path, FILTER_VALIDATE_URL)) {
                continue;
            }
            $this->migrateFile($download->arquivo_path, 'Download #' . $download->id);
        }

        $this->info('Processo finalizado!');
    }

    private function migrateFile($relativePath, $label)
    {
        if (empty($relativePath))
            return;

        // Caminhos Físicos Absolutos
        $publicPath = storage_path('app/public/' . $relativePath);
        $privatePath = storage_path('app/products/' . $relativePath);
        // Caminho fallback (raiz app)
        $rootPath = storage_path('app/' . $relativePath);

        // Verifica onde o arquivo está atualmente
        $sourcePath = null;
        if (file_exists($publicPath) && is_file($publicPath)) {
            $sourcePath = $publicPath;
        } elseif (file_exists($rootPath) && is_file($rootPath)) {
            // Se já estiver na raiz app (mas não no products), vamos mover para products para padronizar
            // Mas cuidado: se 'products' for subpasta de 'app', não mover se já estiver lá
            if (strpos(realpath($rootPath), realpath(storage_path('app/products'))) === false) {
                $sourcePath = $rootPath;
            }
        }

        // Se encontrou a fonte e NÃO existe no destino (para não sobrescrever)
        if ($sourcePath && !file_exists($privatePath)) {
            $this->info("Migrando {$label}: {$relativePath}");

            // Garante que a pasta destino existe
            $dir = dirname($privatePath);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }

            // Move o arquivo
            if (rename($sourcePath, $privatePath)) {
                $this->info("  [OK] Movido com sucesso.");
            } else {
                $this->error("  [ERRO] Falha ao mover arquivo.");
            }
        } elseif (file_exists($privatePath)) {
            $this->comment("  [SKIP] {$label} já está no disco seguro.");
            // Se ainda existir no public, apagar para garantir segurança?
            if ($sourcePath && $sourcePath !== $privatePath) {
                $this->comment("  [CLEAN] Removendo cópia insegura pública...");
                unlink($sourcePath);
            }
        } else {
            $this->warn("  [MISSING] Arquivo físico não encontrado: {$relativePath}");
        }
    }
}
