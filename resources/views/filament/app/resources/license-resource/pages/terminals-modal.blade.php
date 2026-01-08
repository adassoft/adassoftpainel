<div class="space-y-4">
    <div class="overflow-x-auto">
        <table
            class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700 rounded-lg">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">Computador</th>
                    <th scope="col" class="px-6 py-3">MAC Address</th>
                    <th scope="col" class="px-6 py-3">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($terminais as $terminal)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $terminal->NOME_COMPUTADOR ?? 'Não identificado' }}
                        </td>
                        <td class="px-6 py-4 font-mono">
                            {{ $terminal->MAC }}
                        </td>
                        <td class="px-6 py-4">
                            <button type="button"
                                wire:click="mountAction('desvincularTerminal', { terminal_id: {{ $terminal->CODIGO }}, license_id: {{ $license_id }} })"
                                class="text-red-600 hover:text-red-800 font-medium text-xs flex items-center gap-1 border border-red-200 bg-red-50 hover:bg-red-100 px-2 py-1 rounded transition-colors">
                                <x-heroicon-o-trash class="w-4 h-4" /> Desvincular
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <td colspan="3" class="px-6 py-4 text-center">
                            Nenhum terminal encontrado para esta empresa.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>