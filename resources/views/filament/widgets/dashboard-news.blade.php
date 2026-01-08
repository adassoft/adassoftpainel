<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-megaphone class="w-5 h-5 text-primary-500" />
                <span>Notícias e Comunicados</span>
            </div>
        </x-slot>

        @if(count($noticias) > 0)
            <div class="space-y-4">
                @foreach($noticias as $news)
                    <div
                        class="p-4 border rounded-lg bg-white dark:bg-gray-800 dark:border-gray-700 hover:shadow-sm transition-shadow">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h3 class="font-bold text-lg text-gray-800 dark:text-gray-100 flex items-center gap-2">
                                    {{ $news->titulo }}
                                    @if($news->software)
                                        <span
                                            class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                                            {{ $news->software->nome_software }}
                                        </span>
                                    @endif

                                    @if($news->prioridade === 'alta')
                                        <span
                                            class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10 animate-pulse">
                                            Alta Prioridade
                                        </span>
                                    @endif
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $news->created_at?->format('d/m/Y H:i') ?? 'Data n/d' }}
                                    @if($news->tipo === 'automatico')
                                        • <span class="text-gray-400 italic">Mensagem Automática</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="prose prose-sm max-w-none text-gray-600 dark:text-gray-300 mb-3">
                            {!! $news->conteudo !!}
                        </div>

                        @if($news->link_acao)
                            <div class="mt-2">
                                <a href="{{ $news->link_acao }}" target="_blank"
                                    class="inline-flex items-center text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                                    Acessar Link
                                    <x-heroicon-m-arrow-top-right-on-square class="w-4 h-4 ml-1" />
                                </a>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                <x-heroicon-o-chat-bubble-left-ellipsis class="w-6 h-6 mx-auto mb-2 opacity-50" />
                <p>Nenhuma notícia recente.</p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>