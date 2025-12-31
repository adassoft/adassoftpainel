@php
    $obs = json_decode($record->observacoes, true);
    $token = $obs['token'] ?? null;
@endphp

<div class="space-y-4">
    @if($token)
        <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Token de Ativação</label>
            <div class="relative">
                <textarea readonly rows="6"
                    class="block p-2.5 w-full text-xs font-mono text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    onclick="this.select()">{{ $token }}</textarea>

                <button type="button" x-data="{ copied: false }" @click="
                            window.navigator.clipboard.writeText('{{ $token }}');
                            copied = true;
                            setTimeout(() => copied = false, 2000);
                        "
                    class="absolute top-2 right-2 p-2 bg-white dark:bg-gray-600 rounded-lg border border-gray-200 dark:border-gray-500 shadow-sm hover:bg-gray-100 dark:hover:bg-gray-500 transition-colors">
                    <x-heroicon-o-clipboard-document x-show="!copied" class="w-4 h-4 text-gray-500 dark:text-gray-300" />
                    <x-heroicon-o-check x-show="copied" class="w-4 h-4 text-green-500" style="display: none;" />
                </button>
            </div>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                Copie este token e insira no instalador/sistema do cliente para validação offline.
            </p>
        </div>
    @else
        <div class="p-4 text-center text-gray-900 dark:text-white bg-red-50 dark:bg-red-900/20 rounded-lg">
            <x-heroicon-o-exclamation-circle class="w-8 h-8 mx-auto mb-2 text-red-500" />
            <p>Nenhum token encontrado apra este serial.</p>
        </div>
    @endif
</div>