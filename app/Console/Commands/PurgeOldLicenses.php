<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\License;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PurgeOldLicenses extends Command
{
    protected $signature = 'license:purge-old 
                            {--date=2025-12-31 : Data de corte para expiração (Y-m-d)} 
                            {--force : Forçar execução sem confirmação}';

    protected $description = 'Exclui permanentemente licenças (não-vitalícias) expiradas até o final da data de corte.';

    public function handle()
    {
        $dateInput = $this->option('date');
        $force = $this->option('force');

        try {
            // Define o fim do dia da data informada para incluir todas as licenças daquele dia
            $cutOffDate = Carbon::parse($dateInput)->endOfDay();
        } catch (\Exception $e) {
            $this->error("Data inválida: {$dateInput}");
            return;
        }

        // Query principal
        // - Expiradas até a data de corte
        // - APENAS NÃO Vitalícias (segurança)
        $query = License::where('data_expiracao', '<=', $cutOffDate)
            ->where('vitalicia', 0); // Proteção extra: nunca deletar vitalícias por script de limpeza de validade

        $count = $query->count();

        if ($count === 0) {
            $this->info("Nenhuma licença encontrada expirada em ou antes de {$cutOffDate->format('d/m/Y H:i:s')}.");
            return;
        }

        $this->alert("ATENÇÃO: PROCESSO DE LIMPEZA DE DADOS");
        $this->info("Você está prestes a excluir PERMANENTEMENTE:");
        $this->comment("- {$count} Licenças expiradas até {$cutOffDate->format('d/m/Y')}");
        $this->comment("- Todos os registros de terminais vinculados a essas licenças");
        $this->comment("- Todos os registros de instalações vinculados a essas licenças");
        $this->newLine();
        $this->info("NOTA: O cadastro das EMPRESAS (Clientes) e USUÁRIOS será PRESERVADO.");

        if (!$force && !$this->confirm('Tem certeza absoluta que deseja continuar? Esta operação é IRREVERSÍVEL.')) {
            $this->info('Operação cancelada pelo usuário.');
            return;
        }

        $this->info("Iniciando exclusão em massa...");
        $bar = $this->output->createProgressBar($count);

        // Processamento em lotes para eficiência e evitar estouro de memória
        // O chunkById normal pode pular registros se ordenarmos por ID e deletarmos,
        // mas aqui vamos pegar IDs e deletar, então o chunkById é seguro se a query base for estável.
        // Porem, como estamos deletando, o "page 1" muda. O chunkById usa "Start ID", então é seguro.

        $query->chunkById(500, function ($licenses) use ($bar) {
            $ids = $licenses->pluck('id')->toArray();

            if (empty($ids))
                return;

            DB::transaction(function () use ($ids) {
                // 1. Limpar Pivot Terminais (se tabela existir e houver registros)
                DB::table('terminais_software')->whereIn('licenca_id', $ids)->delete();

                // 2. Limpar Instalações (se tabela existir e houver registros)
                DB::table('licenca_instalacoes')->whereIn('licenca_id', $ids)->delete();

                // 3. Deletar as Licenças
                License::whereIn('id', $ids)->delete();
            });

            $bar->advance(count($ids));
        });

        $bar->finish();
        $this->newLine();
        $this->newLine();
        $this->info("Limpeza concluída com sucesso! {$count} licenças foram removidas.");

        // Sugestão de otimização
        $this->comment("Sugestão: Rode 'php artisan optimize:clear' para atualizar quaisquer caches de contagem no painel.");
    }
}
