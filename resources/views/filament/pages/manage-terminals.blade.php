<x-filament-panels::page>
    <!-- Filtros -->
    <x-filament-panels::form>
        {{ $this->form }}
    </x-filament-panels::form>

    <!-- Validar Token -->
    <div class="mt-6 mb-2 p-4 bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 block">Validar Token (Offline/Seguro)</label>
        <div class="flex gap-2">
            <input 
                type="text" 
                wire:model="tokenToValidate" 
                placeholder="Cole o token completo aqui (ex: eyJ...)" 
                class="flex-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-primary-500 sm:text-sm"
            >
            <x-filament::button wire:click="checkToken" icon="heroicon-m-check-badge">
                Verificar
            </x-filament::button>
        </div>
    </div>
    
    <div class="space-y-6 mt-4">
        @forelse($licencas as $licenca)
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <!-- Header do Card -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-white/10 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-950 dark:text-white flex items-center gap-2">
                            {{ $licenca->software->nome_software }} 
                            <span class="text-gray-400">&bull;</span> 
                            <span class="font-mono text-base text-gray-600 dark:text-gray-300">Serial {{ $licenca->serial_atual }}</span>
                        </h3>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ $licenca->company->razao ?? 'Empresa N/A' }} 
                            &mdash; Válida até {{ optional($licenca->data_expiracao)->format('d/m/Y') ?? 'Vitalício' }}
                        </p>
                    </div>

                    <div class="flex gap-2 text-xs font-bold uppercase tracking-wide">
                        <span class="px-2 py-1 rounded-md border" style="background-color: #dbeafe; color: #1e40af; border-color: #bfdbfe;">
                            Permitidos: {{ $licenca->terminais_permitidos }}
                        </span>
                        <span class="px-2 py-1 rounded-md border" style="background-color: #dcfce7; color: #166534; border-color: #bbf7d0;">
                            Ativos: {{ $licenca->total_ativos }}
                        </span>
                        <span class="px-2 py-1 rounded-md border" style="background-color: #e0f2fe; color: #0369a1; border-color: #bae6fd;">
                            Disponíveis: {{ $licenca->total_disponiveis }}
                        </span>
                    </div>
                </div>

                <!-- Tabela de Terminais -->
                <div class="p-0 overflow-x-auto">
                    @if(count($licenca->dados_terminais) > 0)
                        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-300 border-b dark:border-white/10">
                                <tr>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3">Computador</th>
                                    <th class="px-6 py-3">MAC</th>
                                    <th class="px-6 py-3">Instalação ID</th>
                                    <th class="px-6 py-3">Último Registro</th>
                                    <th class="px-6 py-3 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($licenca->dados_terminais as $terminal)
                                    <tr class="bg-white border-b dark:bg-gray-900 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                        <td class="px-6 py-4">
                                            @if(($terminal['ativo'] ?? 0) == 1)
                                                <span class="items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20">Ativo</span>
                                            @else
                                                <span class="items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10 dark:bg-red-400/10 dark:text-red-400 dark:ring-red-400/20">Desvinculado</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                            {{ $terminal['nome_computador'] ?: 'Não identificado' }}
                                        </td>
                                        <td class="px-6 py-4 font-mono text-xs">
                                            {{ $terminal['mac_address'] }}
                                        </td>
                                        <td class="px-6 py-4 font-mono text-xs text-gray-500">
                                            {{ $terminal['instalacao_id'] }}
                                        </td>
                                        <td class="px-6 py-4">
                                            {{ \Carbon\Carbon::parse($terminal['ultimo_registro'] ?? $terminal['ultima_atividade'] ?? now())->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="px-6 py-4 text-right flex justify-end gap-2">
                                            @if(($terminal['ativo'] ?? 0) == 1)
                                                <x-filament::button
                                                    size="xs"
                                                    color="warning"
                                                    wire:click="disable({{ $licenca->id }}, {{ $terminal['terminal_id'] ?? 'null' }})"
                                                    wire:confirm="Tem certeza que deseja desabilitar este terminal? Ele perderá acesso imediato."
                                                >
                                                    Desabilitar
                                                </x-filament::button>
                                            @endif

                                            <x-filament::button
                                                size="xs"
                                                color="danger"
                                                outlined
                                                wire:click="remove({{ $licenca->id }}, {{ $terminal['terminal_id'] ?? 'null' }}, '{{ $terminal['instalacao_id'] ?? '' }}', '{{ $terminal['mac_address'] ?? '' }}')"
                                                wire:confirm="Atenção: Isso removerá o registro permanente desta instalação. Deseja continuar?"
                                            >
                                                Remover
                                            </x-filament::button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                         <div class="px-6 py-8 text-center text-gray-500 dark:text-gray-400 bg-gray-50/50 dark:bg-gray-800/20">
                            Nenhuma instalação registrada para esta licença.
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center p-12 bg-white rounded-xl shadow-sm dark:bg-gray-900">
                <div class="text-gray-500 dark:text-gray-400">Nenhuma licença encontrada.</div>
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
