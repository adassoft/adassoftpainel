<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
        <!-- Generator Column -->
        <div class="flex flex-col gap-4">
            {{ $this->generatorForm }}

            @if($generatedSerial)
                <div
                    class="p-4 bg-green-50 dark:bg-green-900/10 border border-green-200 dark:border-green-800 rounded-lg shadow-sm">
                    <div class="flex items-center gap-2 mb-2">
                        <x-heroicon-o-check-circle class="w-6 h-6 text-green-600 dark:text-green-400" />
                        <h3 class="text-lg font-bold text-green-700 dark:text-green-400">Serial Gerado com Sucesso!</h3>
                    </div>

                    <div class="relative group">
                        <code
                            class="block w-full bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-700 font-mono text-xl text-center text-gray-800 dark:text-gray-200 mb-4 break-all selection:bg-green-200 dark:selection:bg-green-900">
                                            {{ $generatedSerial }}
                                        </code>
                    </div>

                    @if($generatedToken)
                        <div class="mt-4 relative group/token">
                            <h4
                                class="text-sm font-bold text-gray-600 dark:text-gray-400 mb-1 flex items-center justify-between gap-1">
                                <span class="flex items-center gap-1"><x-heroicon-o-shield-check class="w-4 h-4" /> Token
                                    Assinado</span>
                            </h4>

                            <div class="relative">
                                <textarea readonly onclick="this.select()"
                                    class="w-full text-xs font-mono p-2 pr-10 border rounded bg-gray-50 dark:bg-gray-950 text-gray-500 dark:text-gray-400 resize-y focus:outline-none focus:ring-1 focus:ring-green-500"
                                    rows="3">{{ $generatedToken }}</textarea>

                                <button type="button" x-data="{ copied: false }" @click="
                                                        window.navigator.clipboard.writeText('{{ $generatedToken }}');
                                                        copied = true;
                                                        setTimeout(() => copied = false, 2000);
                                                    "
                                    class="absolute top-2 right-2 p-1.5 bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700 text-gray-500 hover:text-green-600 dark:text-gray-400 dark:hover:text-green-400 shadow-sm transition-colors"
                                    title="Copiar Token">
                                    <x-heroicon-o-clipboard-document x-show="!copied" class="w-4 h-4" />
                                    <x-heroicon-o-check x-show="copied" class="w-4 h-4 text-green-500" style="display: none;" />
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Este token pode ser usado para validação offline/segura.</p>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Validator Column -->
        <div class="flex flex-col gap-4">
            {{ $this->validatorForm }}

            @if($validationResult)
                <div @class([
                    'p-4 border rounded-lg shadow-sm transition-all duration-300 fade-in',
                    'bg-green-50 border-green-200 dark:bg-green-900/10 dark:border-green-800' => $validationResult === 'valid',
                    'bg-red-50 border-red-200 dark:bg-red-900/10 dark:border-red-800' => $validationResult !== 'valid',
                ])>
                    <div class="flex items-center gap-2 mb-3">
                        @if($validationResult === 'valid')
                            <x-heroicon-o-check-badge class="w-6 h-6 text-green-600 dark:text-green-400" />
                            <h3 class="text-lg font-bold text-green-700 dark:text-green-400">Serial Válido</h3>
                        @else
                            <x-heroicon-o-x-circle class="w-6 h-6 text-red-600 dark:text-red-400" />
                            <h3 class="text-lg font-bold text-red-700 dark:text-red-400">Serial Inválido</h3>
                        @endif
                    </div>

                    @if($validationDetails)
                        <div class="bg-white/50 dark:bg-black/20 rounded p-3">
                            <ul class="space-y-2 text-sm">
                                @foreach($validationDetails as $key => $value)
                                    <li class="flex justify-between border-b last:border-0 border-gray-200/50 pb-1 last:pb-0">
                                        <span class="font-semibold text-gray-600 dark:text-gray-400">{{ $key }}:</span>
                                        <span class="text-gray-800 dark:text-gray-200 font-medium">{{ $value }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="mt-8 pt-6 border-t dark:border-gray-800">
        <h2 class="text-xl font-bold mb-4 flex items-center gap-2 text-gray-800 dark:text-white">
            <x-heroicon-o-clock class="w-6 h-6 text-gray-500" />
            Histórico de Seriais
        </h2>

        <div class="mb-6">
            {{ $this->historyFilterForm }}
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>