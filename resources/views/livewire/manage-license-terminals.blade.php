<div class="overflow-x-auto relative">
    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th class="px-4 py-2">Terminal / Instalação</th>
                <th class="px-4 py-2">MAC Address</th>
                <th class="px-4 py-2">Status</th>
                <th class="px-4 py-2 text-right">Ações</th>
            </tr>
        </thead>
        <tbody>
            @foreach($terminals as $terminal)
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td class="px-4 py-2 font-medium text-gray-900 dark:text-white">
                        <div class="flex flex-col">
                            <span>{{ $terminal['nome_computador'] ?? 'Desconhecido' }}</span>
                            <span class="text-xs text-gray-400">ID: {{ $terminal['terminal_id'] ?? $terminal['instalacao_id'] ?? '-' }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-2 font-mono">{{ $terminal['mac_address'] }}</td>
                    <td class="px-4 py-2">
                         @if(($terminal['ativo'] ?? 0) == 1)
                            <span class="bg-green-100 text-green-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-300">Ativo</span>
                         @else
                             @if(($terminal['source'] ?? '') == 'installation')
                                <span class="bg-yellow-100 text-yellow-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-yellow-900 dark:text-yellow-300">Pendente</span>
                             @else
                                <span class="bg-red-100 text-red-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-red-900 dark:text-red-300">Inativo</span>
                             @endif
                         @endif
                         <div class="text-xs text-gray-400 mt-1">
                            {{ \Carbon\Carbon::parse($terminal['ultima_atividade'] ?? $terminal['ultimo_registro'] ?? null)->format('d/m/Y H:i') }}
                         </div>
                    </td>
                    <td class="px-4 py-2 text-right">
                        @if($confirmingMac === $terminal['mac_address'])
                            <div class="flex items-center justify-end space-x-2">
                                <span class="text-xs text-red-600 font-bold hidden sm:inline" style="color: #dc2626;">Tem certeza?</span>
                                
                                <button 
                                    wire:click="removeTerminal('{{ $terminal['mac_address'] }}', '{{ $terminal['source'] }}')"
                                    class="text-white bg-red-700 hover:bg-red-800 font-medium rounded-lg text-xs px-3 py-1.5"
                                    style="background-color: #b91c1c; color: white;"
                                >
                                    Sim
                                </button>
                                
                                <button 
                                    wire:click="cancelRemoval"
                                    class="text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 font-medium rounded-lg text-xs px-3 py-1.5"
                                    style="background-color: white; color: black; border: 1px solid #d1d5db;"
                                >
                                    Não
                                </button>
                            </div>
                        @else
                            <button 
                                type="button"
                                wire:click="confirmRemoval('{{ $terminal['mac_address'] }}')"
                                class="text-white bg-red-600 hover:bg-red-700 font-medium rounded-lg text-xs px-3 py-1.5 inline-flex items-center"
                                style="background-color: #dc2626; color: white;"
                            >
                                <svg class="w-3 h-3 mr-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 20" style="width: 12px; height: 12px; margin-right: 4px;">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h16M7 8v8m4-8v8M7 1h4a1 1 0 0 1 1 1v3H6V2a1 1 0 0 1 1-1ZM3 5h12v13a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V5Z"/>
                                </svg>
                                Remover
                            </button>
                        @endif
                    </td>
                </tr>
            @endforeach
            
            @if(empty($terminals))
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                        Nenhum terminal encontrado nesta licença.
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
    
    <div wire:loading.flex class="absolute inset-0 bg-white/50 dark:bg-gray-800/50 items-center justify-center z-10">
        <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </div>
</div>
