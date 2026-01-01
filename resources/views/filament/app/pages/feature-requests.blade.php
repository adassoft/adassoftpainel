<x-filament::page>
    <div class="flex flex-col gap-6">
        
        {{-- Tabs de Filtro --}}
        <div class="flex gap-2 overflow-x-auto pb-2 border-b border-gray-200 dark:border-gray-800">
            @php
                $tabs = [
                    'all' => 'Todas',
                    'voting' => 'Em Votação',
                    'planned' => 'Planejadas',
                    'in_progress' => 'Em Andamento',
                    'completed' => 'Concluídas',
                ];
            @endphp

            @foreach($tabs as $key => $label)
                <button 
                    wire:click="$set('filterStatus', '{{ $key }}')"
                    class="px-4 py-2 text-sm font-medium rounded-t-lg transition-colors
                           {{ $filterStatus === $key 
                              ? 'bg-primary-50 text-primary-600 border-b-2 border-primary-600 dark:bg-gray-800 dark:text-primary-400' 
                              : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' 
                           }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Grid de Sugestões --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($suggestions as $suggestion)
                @php
                    $isVoted = $suggestion->voted_by_user > 0;
                    $statusColors = [
                        'pending' => 'gray',
                        'voting' => 'warning',
                        'planned' => 'info',
                        'in_progress' => 'primary', // Filament v3 uses 'primary' typically
                        'completed' => 'success',
                        'rejected' => 'danger',
                    ];
                    $statusLabels = [
                        'pending' => 'Pendente',
                        'voting' => 'Votação Aberta',
                        'planned' => 'Planejado',
                        'in_progress' => 'Em Desenvolvimento',
                        'completed' => 'Concluído',
                        'rejected' => 'Recusado',
                    ];
                @endphp

                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-5 flex flex-col h-full transition hover:shadow-md">
                    
                    <div class="flex justify-between items-start mb-3">
                        <x-filament::badge color="{{ $statusColors[$suggestion->status] }}">
                            {{ $statusLabels[$suggestion->status] }}
                        </x-filament::badge>
                        
                        @if($suggestion->software)
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">
                                {{ $suggestion->software->nome_software }}
                            </span>
                        @endif
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 leading-tight">
                        {{ $suggestion->title }}
                    </h3>

                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-6 flex-grow whitespace-pre-line">
                        {{ Str::limit($suggestion->description, 150) }}
                    </p>

                    <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-xs font-bold text-gray-600 dark:text-gray-300">
                                {{ substr($suggestion->user->name ?? '?', 0, 1) }}
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[100px]">
                                {{ $suggestion->user->name ?? 'Anônimo' }}
                            </span>
                        </div>

                        {{-- Botão de Votar --}}
                        <button 
                            wire:click="toggleVote({{ $suggestion->id }})"
                            wire:loading.attr="disabled"
                            class="group flex flex-col items-center justify-center w-12 h-14 rounded-lg border transition-all cursor-pointer
                                   {{ $isVoted 
                                      ? 'bg-primary-50 border-primary-500 text-primary-600 dark:bg-primary-900/20 dark:border-primary-500 dark:text-primary-400' 
                                      : 'bg-white border-gray-200 hover:border-gray-300 text-gray-500 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700/50' 
                                   }}"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mb-1 transition-transform group-hover:-translate-y-0.5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
                            </svg>
                            <span class="font-bold text-sm">{{ $suggestion->votes_count }}</span>
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center">
                    <div class="mx-auto w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                        <x-heroicon-o-light-bulb class="w-8 h-8 text-gray-400" />
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Nenhuma sugestão encontrada</h3>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Seja o primeiro a sugerir uma melhoria!</p>
                </div>
            @endforelse
        </div>

        {{-- Paginação --}}
        <div class="mt-4">
            {{ $suggestions->links() }}
        </div>

    </div>
</x-filament::page>
