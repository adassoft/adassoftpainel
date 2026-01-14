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
                    class="group relative flex flex-col bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 dark:border-gray-700 overflow-hidden h-full">

                    {{-- Badge --}}
                    <div class="absolute top-4 left-4 z-10">
                        <span
                            class="bg-white/90 dark:bg-gray-900/90 backdrop-blur text-gray-700 dark:text-gray-200 text-xs px-3 py-1 rounded-full font-bold shadow-sm border border-gray-100 dark:border-gray-700">
                            Software
                        </span>
                    </div>

                    {{-- Image Area --}}
                    <div
                        class="h-48 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center relative overflow-hidden">
                        @if($sw->imagem)
                            @php
                                $imgSrc = $sw->imagem;
                                if (!filter_var($imgSrc, FILTER_VALIDATE_URL)) {
                                    $imgSrc = \Illuminate\Support\Facades\Storage::url($imgSrc);
                                }
                            @endphp
                            <img src="{{ $imgSrc }}"
                                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                        @else
                            <div class="flex flex-col items-center justify-center p-6 text-center">
                                <div
                                    class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-full mb-3 group-hover:scale-110 transition-transform duration-300">
                                    <x-filament::icon icon="heroicon-o-cube"
                                        class="h-10 w-10 text-blue-600 dark:text-blue-400" />
                                </div>
                                <span
                                    class="text-2xl font-bold text-gray-300 dark:text-gray-700 tracking-tight font-serif italic">
                                    Adassoft
                                </span>
                            </div>
                        @endif

                        {{-- Overlay Gradient --}}
                        <div
                            class="absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-white dark:from-gray-800 to-transparent opacity-50">
                        </div>
                    </div>

                    {{-- Content --}}
                    <div class="p-6 flex flex-col flex-1">
                        <div class="mb-4">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 leading-tight">
                                {{ $sw->nome_software }}
                            </h3>

                            <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-3 leading-relaxed">
                                {{ $sw->descricao ?? 'Solução completa para gestão e automação do seu negócio. Aumente sua produtividade hoje.' }}
                            </p>
                        </div>

                        {{-- Footer / Price --}}
                        <div class="mt-auto flex flex-col gap-4">

                            @php
                                $minPrice = $sw->plans->min('valor');
                            @endphp

                            <div class="flex items-end justify-between pt-4 border-t border-gray-100 dark:border-gray-700">
                                <div class="flex flex-col">
                                    <span class="text-xs text-gray-400 font-medium uppercase tracking-wide">A partir
                                        de</span>
                                    @if($minPrice)
                                        <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                                            R$ {{ number_format($minPrice, 2, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-lg font-bold text-gray-400">Sob Consulta</span>
                                    @endif
                                </div>
                            </div>

                            <a href="{{ route('product.show', $sw->id) }}"
                                class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg shadow-blue-600/20 text-center transition-all transform hover:-translate-y-0.5 active:translate-y-0 active:shadow-none flex items-center justify-center gap-2">
                                Comprar
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach

            @if($softwares->isEmpty())
                <div
                    class="col-span-full p-8 text-center bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-dashed border-gray-300 dark:border-gray-700">
                    <x-filament::icon icon="heroicon-o-check-badge" class="h-12 w-12 text-gray-400 mx-auto mb-3" />
                    <p class="text-gray-500 dark:text-gray-400 font-medium">Você já possui todos os softwares do nosso
                        catálogo atual!</p>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>