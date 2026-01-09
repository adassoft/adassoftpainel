<x-filament-panels::page>
    <div wire:init="loadProducts">
        @if($isLoading)
            <div class="flex justify-center p-4">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500" />
                <span class="ml-2">Carregando produtos do Mercado Livre...</span>
            </div>
        @else
            @if(empty($products))
                <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow text-center">
                    <p class="text-gray-500">Nenhum anúncio ativo encontrado.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($products as $product)
                        <div
                            class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden border border-gray-200 dark:border-gray-700 hover:shadow-lg transition">
                            <div class="w-full h-48 bg-gray-100 flex items-center justify-center relative">
                                <img src="{{ $product['thumbnail'] ?? '' }}" alt="{{ $product['title'] }}"
                                    class="max-h-full max-w-full object-contain">
                                <span
                                    class="absolute top-2 right-2 px-2 py-1 text-xs font-bold rounded {{ $product['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $product['status'] }}
                                </span>
                            </div>
                            <div class="p-4 flex flex-col h-full justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white line-clamp-2"
                                        title="{{ $product['title'] }}">
                                        {{ $product['title'] }}
                                    </h3>
                                    <div class="mt-2 flex items-center justify-between">
                                        <span class="text-xl font-bold text-gray-900 dark:text-white">
                                            R$ {{ number_format($product['price'], 2, ',', '.') }}
                                        </span>
                                        <span class="text-sm text-gray-500">
                                            Qtd: {{ $product['available_quantity'] }}
                                        </span>
                                    </div>
                                    <div class="mt-1 text-xs text-gray-400">
                                        ID: {{ $product['id'] }}
                                    </div>
                                </div>

                                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                    <x-filament::button tag="a" href="{{ $product['permalink'] }}" target="_blank"
                                        icon="heroicon-m-arrow-top-right-on-square" color="gray" size="sm"
                                        class="w-full justify-center">
                                        Ver no Mercado Livre
                                    </x-filament::button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</x-filament-panels::page>