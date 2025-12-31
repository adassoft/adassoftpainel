<?php

namespace App\Observers;

use App\Models\Software;
use App\Models\News;

class SoftwareObserver
{
    /**
     * Handle the Software "created" event.
     */
    public function created(Software $software): void
    {
        News::create([
            'software_id' => $software->id,
            'titulo' => 'Novo Software Disponível: ' . $software->nome_software,
            'conteudo' => "O software <strong>{$software->nome_software}</strong> foi adicionado ao catálogo. Confira os detalhes e planos disponíveis no painel.",
            'prioridade' => 'normal',
            'ativa' => true,
            'publico' => 'revenda', // Apenas revendas
            'tipo' => 'automatico',
        ]);
    }

    /**
     * Handle the Software "updated" event.
     */
    public function updated(Software $software): void
    {
        // Opcional: Notificar update relevante?
    }

    /**
     * Handle the Software "deleted" event.
     */
    public function deleted(Software $software): void
    {
        //
    }

    /**
     * Handle the Software "restored" event.
     */
    public function restored(Software $software): void
    {
        //
    }

    /**
     * Handle the Software "force deleted" event.
     */
    public function forceDeleted(Software $software): void
    {
        //
    }
}
