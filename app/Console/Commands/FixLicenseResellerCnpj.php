<?php

namespace App\Console\Commands;

use App\Models\License;
use Illuminate\Console\Command;

class FixLicenseResellerCnpj extends Command
{
    protected $signature = 'fix:legacy-license-reseller';
    protected $description = 'Corrige o CNPJ da revenda nas licenças importadas';

    public function handle()
    {
        $targetCnpj = '04733736000120';

        $licenses = License::where('observacoes', 'LIKE', '%Importado do Legado%')
            ->where('cnpj_revenda', '!=', $targetCnpj)
            ->get();

        $count = $licenses->count();

        if ($count === 0) {
            $this->info("Nenhuma licença importada com CNPJ incorreto encontrada.");
            return;
        }

        if ($this->confirm("Encontradas $count licenças importadas com CNPJ diferente de $targetCnpj. Deseja corrigir?")) {
            License::where('observacoes', 'LIKE', '%Importado do Legado%')
                ->where('cnpj_revenda', '!=', $targetCnpj)
                ->update(['cnpj_revenda' => $targetCnpj]);

            $this->info("Sucesso! $count licenças foram atualizadas para o CNPJ $targetCnpj.");
        }
    }
}
