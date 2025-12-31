<?php

namespace App\Observers;

use App\Models\ResellerConfig;

class ResellerConfigObserver
{
    /**
     * Handle the ResellerConfig "saving" event.
     */
    public function saving(ResellerConfig $resellerConfig): void
    {
        // Se este registro está sendo marcado como padrão
        if ($resellerConfig->is_default) {
            // Desmarca todos os outros registros que são padrão (exceto o atual se já existir)
            ResellerConfig::where('id', '!=', $resellerConfig->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }
    }
}
