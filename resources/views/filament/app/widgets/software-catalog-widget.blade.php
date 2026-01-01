<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Catálogo de Softwares
        </x-slot>
        <x-slot name="description">
            Soluções recomendadas para complementar seu portfólio.
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($softwares as $sw)
                <div
                    class="flex flex-col bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm hover:shadow-md transition-shadow">

                    <div
                        class="h-32 bg-gray-50 dark:bg-gray-900/50 flex items-center justify-center border-b border-gray-100 dark:border-gray-700 relative group">
                        <div class="bg-primary-50 dark:bg-primary-900/40 p-4 rounded-full">
                            <x-filament::icon icon="heroicon-o-cube" class="h-10 w-10 text-primary-500" />
                        </div>
                    </div>

                    <div class="p-5 flex flex-col flex-1">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">
                            {{ $sw->nome_software }}
                        </h3>

                        @if($sw->versao)
                            <p class="text-xs text-gray-500 mb-3 font-mono">v{{ $sw->versao }}</p>
                        @endif

                        <div class="flex flex-wrap gap-2 mb-4">
                            @if($sw->linguagem)
                                <span
                                    class="px-2 py-0.5 bg-primary-50 dark:bg-primary-950/50 border border-primary-100 dark:border-primary-900 text-xs rounded text-primary-700 dark:text-primary-400">
                                    {{ $sw->linguagem }}
                                </span>
                            @endif
                            @if($sw->plataforma)
                                <span
                                    class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-xs rounded text-gray-600 dark:text-gray-400">
                                    {{ $sw->plataforma }}
                                </span>
                            @endif
                        </div>

                        <div class="mt-auto">
                            <x-filament::button tag="a" href="{{ route('product.show', $sw->id) }}" color="gray" outlined
                                class="w-full">
                                Saiba Mais
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            @endforeach

            @if($softwares->isEmpty())
                <div class="col-span-full p-6 text-center text-gray-500 dark:text-gray-400 italic">
                    Você já possui todos os softwares do nosso catálogo atual!
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>