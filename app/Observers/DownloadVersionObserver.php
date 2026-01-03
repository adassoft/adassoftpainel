<?php

namespace App\Observers;

use App\Models\DownloadVersion;

class DownloadVersionObserver
{
    /**
     * Handle the DownloadVersion "created" event.
     */
    public function created(DownloadVersion $downloadVersion): void
    {
        $this->syncParent($downloadVersion->download_id);
    }

    public function updated(DownloadVersion $downloadVersion): void
    {
        $this->syncParent($downloadVersion->download_id);
    }

    public function deleted(DownloadVersion $downloadVersion): void
    {
        $this->syncParent($downloadVersion->download_id);
    }

    protected function syncParent($downloadId)
    {
        if (!$downloadId)
            return;

        $download = \App\Models\Download::find($downloadId);
        if (!$download)
            return;

        // Encontrar a versão mais recente absoluta (por data)
        // Se houver múltiplas (ex: Win/Linux na mesma data), pega a primeira.
        // O ideal é a data de lançamento.
        $latest = $download->versions()->orderBy('data_lancamento', 'desc')->first();

        if ($latest) {
            $download->update([
                'versao' => $latest->versao,
                'arquivo_path' => $latest->arquivo_path, // Caminho do arquivo "padrão" (geralmente Windows)
                'tamanho' => $latest->tamanho,
            ]);
        }
    }
}
