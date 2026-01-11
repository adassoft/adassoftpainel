<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6">

        <!-- Coluna Esquerda: Formulário de Criação -->
        <div>
            <x-filament::section icon="heroicon-o-plus-circle">
                <x-slot name="heading">Criar nova API key</x-slot>

                {{ $this->form }}

            </x-filament::section>

            <!-- Aviso de Key Gerada -->
            @if($generatedKey)
                <div
                    class="mt-4 p-4 border border-yellow-200 bg-yellow-50 dark:bg-yellow-900/20 dark:border-yellow-700 rounded-lg">
                    <h3 class="text-sm font-bold text-yellow-800 dark:text-yellow-200 mb-2">
                        Nova API Key Gerada
                    </h3>
                    <div class="relative">
                        <code
                            class="block w-full p-2 text-xs font-mono bg-white dark:bg-black rounded border border-yellow-200 dark:border-yellow-800 break-all select-all">
                                    {{ $generatedKey }}
                                </code>
                    </div>
                    <p class="mt-2 text-xs text-yellow-700 dark:text-yellow-300">
                        Copie agora. Ela não será exibida novamente.
                    </p>
                </div>
            @endif
        </div>

        <!-- Coluna Direita: Tabela de Chaves -->
        <div>
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex justify-between items-center">
                        <span>Chaves Recentes</span>
                        <span class="text-xs font-normal text-gray-500">Exibindo últimas 50</span>
                    </div>
                </x-slot>

                {{ $this->table }}

            </x-filament::section>

            <div class="mt-6">
                <x-filament::section icon="heroicon-o-archive-box">
                    <x-slot name="heading">Backup automático</x-slot>
                    <div class="prose prose-sm dark:prose-invert text-gray-600 dark:text-gray-400">
                        <p>Configure o script de backup no cron do servidor:</p>
                        <code
                            class="text-xs bg-gray-100 dark:bg-gray-800 p-1 rounded">php artisan backup:licenses</code>
                        <ul class="text-xs mt-2 list-disc pl-4">
                            <li>Retenção sugerida: 7-30 dias</li>
                            <li>Inclui tabelas: historico_seriais, licencas_ativas, log_validacoes, api_keys</li>
                        </ul>
                    </div>
                </x-filament::section>
            </div>
        </div>
    </div>
</x-filament-panels::page>