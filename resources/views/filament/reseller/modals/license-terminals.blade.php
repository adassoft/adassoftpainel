<div class="overflow-x-auto">
    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th class="px-4 py-2">Terminal / Instalação</th>
                <th class="px-4 py-2">MAC Address</th>
                <th class="px-4 py-2">Status</th>
                <th class="px-4 py-2 text-right">Última Atividade</th>
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
                    </td>
                    <td class="px-4 py-2 text-right">
                        {{ \Carbon\Carbon::parse($terminal['ultima_atividade'] ?? $terminal['ultimo_registro'] ?? null)->format('d/m/Y H:i') }}
                    </td>
                </tr>
            @endforeach
            
            @if(empty($terminals))
                <tr>
                    <td colspan="4" class="px-4 py-4 text-center text-gray-500">Nenhum terminal encontrado.</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
