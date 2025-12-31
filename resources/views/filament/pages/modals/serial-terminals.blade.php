@php
    // Busca a licença ativa correspondente
    $licenca = \App\Models\License::where('empresa_codigo', $record->empresa_codigo)
        ->where('software_id', $record->software_id)
        ->first();

    // Usa o acessor merged_terminals que unifica terminais oficiais e instalações pendentes
    $terminais = $licenca ? $licenca->merged_terminals : [];
@endphp

<div class="space-y-4">
    @if(count($terminais) > 0)
        <div class="overflow-x-auto border rounded-lg dark:border-gray-700">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-4 py-3">Computador</th>
                        <th scope="col" class="px-4 py-3">MAC</th>
                        <th scope="col" class="px-4 py-3">Vínculo</th>
                        <th scope="col" class="px-4 py-3 text-right">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($terminais as $item)
                        @php
                            // Trata array retornado pelo merged_terminals
                            $item = (array) $item;
                            $nome = $item['nome_computador'] ?? 'Instalação Pendente';
                            $mac = $item['mac_address'] ?? 'N/A';
                            $data = $item['data_vinculo'] ?? ($item['ultimo_registro'] ?? null);

                            // IDs para remoção
                            $termId = $item['terminal_codigo'] ?? null;
                            $instId = $item['instalacao_id'] ?? null;
                        @endphp
                        <tr
                            class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                {{ $nome }}
                            </td>
                            <td class="px-4 py-2 font-mono text-xs">
                                {{ $mac }}
                            </td>
                            <td class="px-4 py-2 text-xs">
                                {{ $data ? \Carbon\Carbon::parse($data)->format('d/m/Y H:i') : '-' }}
                            </td>
                            <td class="px-4 py-2 text-right">
                                <x-filament::button size="xs" color="danger" outlined
                                    wire:click="removeTerminal({{ $licenca->id }}, '{{ $termId }}', '{{ $instId }}', '{{ $mac }}')"
                                    wire:confirm="Tem certeza que deseja remover este terminal/instalação?">
                                    Remover
                                </x-filament::button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="p-4 text-center text-gray-500 bg-gray-50 dark:bg-gray-800 rounded-lg border dark:border-gray-700">
            <x-heroicon-o-computer-desktop class="w-12 h-12 mx-auto mb-2 text-gray-400" />
            <p>Nenhum terminal vinculado a esta licença no momento.</p>
        </div>
    @endif

    @if(!$licenca)
        <p class="text-xs text-red-500 text-center">Licença ativa não encontrada para este serial.</p>
    @endif
</div>