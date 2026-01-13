<x-filament-panels::page>
    <div
        class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 block">
            Validar Token de Licença Offline/Seguro
        </label>

        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
            Cole abaixo o token completo (formato string longa iniciando com <code>eyJ...</code>) para verificar sua
            autenticidade e conteúdo.
        </p>

        <div class="flex gap-2 mb-4">
            <textarea wire:model="tokenToValidate" rows="3" placeholder="Cole o token completo aqui..."
                class="flex-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-primary-500 sm:text-sm font-mono"></textarea>
        </div>

        <div class="flex justify-end">
            <x-filament::button wire:click="checkToken" icon="heroicon-m-check-badge" size="lg">
                Verificar Autenticidade
            </x-filament::button>
        </div>
    </div>

    @if($result)
        <div class="mt-6 p-6 rounded-xl bg-green-50 border border-green-200 dark:bg-green-900/10 dark:border-green-800">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-green-100 dark:bg-green-800 rounded-full text-green-600 dark:text-green-300">
                    <x-heroicon-s-check-badge class="w-6 h-6" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-green-800 dark:text-green-300">Token Válido e Autêntico!</h3>
                    <p class="text-sm text-green-700 dark:text-green-400">A assinatura digital deste token foi verificada
                        com sucesso.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="bg-white dark:bg-gray-800/50 p-3 rounded-lg border border-green-100 dark:border-green-800/30">
                    <span class="block text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">Serial</span>
                    <strong
                        class="text-gray-900 dark:text-white font-mono text-base">{{ $result['serial'] ?? 'N/A' }}</strong>
                </div>

                <div class="bg-white dark:bg-gray-800/50 p-3 rounded-lg border border-green-100 dark:border-green-800/30">
                    <span class="block text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">Validade</span>
                    <strong
                        class="text-gray-900 dark:text-white">{{ isset($result['validade']) ? date('d/m/Y', strtotime($result['validade'])) : 'N/A' }}</strong>
                </div>

                <div class="bg-white dark:bg-gray-800/50 p-3 rounded-lg border border-green-100 dark:border-green-800/30">
                    <span class="block text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">Terminais
                        Permitidos</span>
                    <strong class="text-gray-900 dark:text-white">{{ $result['terminais'] ?? '?' }}</strong>
                </div>

                <div class="bg-white dark:bg-gray-800/50 p-3 rounded-lg border border-green-100 dark:border-green-800/30">
                    <span class="block text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">Emitido Em</span>
                    <strong
                        class="text-gray-900 dark:text-white">{{ isset($result['emitido_em']) ? date('d/m/Y H:i', strtotime($result['emitido_em'])) : '-' }}</strong>
                </div>

                <div class="bg-white dark:bg-gray-800/50 p-3 rounded-lg border border-green-100 dark:border-green-800/30">
                    <span class="block text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">Empresa /
                        Cliente</span>
                    <strong
                        class="text-gray-900 dark:text-white">{{ $result['empresa_razao'] ?? $result['empresa_codigo'] ?? '-' }}</strong>
                </div>
                <div class="bg-white dark:bg-gray-800/50 p-3 rounded-lg border border-green-100 dark:border-green-800/30">
                    <span class="block text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">Software</span>
                    <strong
                        class="text-gray-900 dark:text-white">{{ $result['software_nome'] ?? $result['software_id'] ?? '-' }}</strong>
                </div>
            </div>

            @if(isset($result['saldo_remanescente_dias']))
                <div
                    class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/10 rounded border border-blue-100 text-xs text-blue-800 dark:text-blue-300">
                    <strong>Nota:</strong> Este token inclui {{ $result['saldo_remanescente_dias'] }} dias de saldo anterior +
                    {{ $result['validade_solicitada_dias'] }} dias novos.
                </div>
            @endif
        </div>
    @endif
</x-filament-panels::page>