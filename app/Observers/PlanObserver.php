<?php

namespace App\Observers;

use App\Models\Plan;
use App\Models\News;

class PlanObserver
{
    /**
     * Handle the Plan "created" event.
     */
    public function created(Plan $plan): void
    {
        // Carrega relacionamento se necessário, ou tenta acessar
        $softwareName = $plan->software ? $plan->software->nome_software : 'Software Indefinido';

        News::create([
            'software_id' => $plan->software_id,
            'titulo' => "Novo Plano: {$softwareName}",
            'conteudo' => "O plano <strong>{$plan->nome_plano}</strong> foi criado para o software {$softwareName}. Valor base: R$ " . number_format((float) $plan->valor, 2, ',', '.'),
            'prioridade' => 'normal',
            'ativa' => true,
            'publico' => 'revenda',
            'tipo' => 'automatico',
        ]);
    }

    /**
     * Handle the Plan "updated" event.
     */
    public function updated(Plan $plan): void
    {
        if ($plan->isDirty('valor')) {
            $softwareName = $plan->software ? $plan->software->nome_software : 'Software Indefinido';
            $oldValor = $plan->getOriginal('valor');
            $newValor = $plan->valor;

            News::create([
                'software_id' => $plan->software_id,
                'titulo' => "Alteração de Preço: {$softwareName} - {$plan->nome_plano}",
                'conteudo' => "O valor do plano <strong>{$plan->nome_plano}</strong> foi alterado de R$ " . number_format((float) $oldValor, 2, ',', '.') . " para R$ " . number_format((float) $newValor, 2, ',', '.') . ".",
                'prioridade' => 'alta',
                'ativa' => true,
                'publico' => 'revenda',
                'tipo' => 'automatico',
            ]);
        }
    }

    /**
     * Handle the Plan "deleted" event.
     */
    public function deleted(Plan $plan): void
    {
        //
    }

    /**
     * Handle the Plan "restored" event.
     */
    public function restored(Plan $plan): void
    {
        //
    }

    /**
     * Handle the Plan "force deleted" event.
     */
    public function forceDeleted(Plan $plan): void
    {
        //
    }
}
