<x-filament-panels::page>
    <div wire:poll.5s="refreshSaldo">
        <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-3">
            <!-- Card Saldo -->
            <div
                class="bg-white rounded-lg shadow p-6 border-l-4 border-l-green-500 flex items-center justify-between dark:bg-gray-800 dark:border-l-green-600">
                <div>
                    <p class="text-xs font-bold text-green-500 uppercase mb-1 tracking-wider">Saldo Disponível</p>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">R$
                        {{ number_format($saldo, 2, ',', '.') }}</h2>
                </div>
                <div class="text-gray-300 dark:text-gray-600">
                    <x-heroicon-m-wallet class="w-10 h-10" />
                </div>
            </div>

            <!-- Card Recarregar -->
            <div class="md:col-span-2 bg-white rounded-lg shadow dark:bg-gray-800">
                <div class="border-b border-gray-200 bg-gray-50 px-6 py-3 dark:bg-gray-700/50 dark:border-gray-700">
                    <h3 class="text-sm font-bold text-primary-600 dark:text-primary-400 uppercase tracking-wide">
                        Recarregar Créditos
                    </h3>
                </div>
                <div class="p-6">
                    <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                        Adicione créditos para ativar licenças para seus clientes de forma automática.<br>
                        <span class="text-xs">Valor mínimo para recarga: <strong>R$
                                {{ number_format($minRecarga, 2, ',', '.') }}</strong></span>
                    </p>

                    <form wire:submit.prevent="generatePix" class="flex flex-col sm:flex-row gap-3 items-center">

                        <!-- Input Group Compacto -->
                        <div class="flex rounded-md shadow-sm w-full sm:w-36">
                            <span
                                class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-100 text-gray-500 text-sm font-bold dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 h-9">
                                R$
                            </span>
                            <input type="text" wire:model="valorRecargaInput"
                                class="focus:ring-primary-500 focus:border-primary-500 flex-1 block w-full rounded-none rounded-r-md sm:text-sm border-gray-300 dark:bg-gray-900 dark:border-gray-600 dark:text-white h-9 py-1 px-3"
                                placeholder="100,00" required>
                        </div>

                        <div class="flex gap-2 w-full sm:w-auto">
                            <!-- Botão Recarregar (Verde) -->
                            <button type="submit"
                                class="w-full sm:w-auto inline-flex items-center justify-center px-4 h-9 text-sm font-bold rounded-md text-white bg-green-500 hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 shadow-sm transition-colors whitespace-nowrap"
                                style="background-color: #1cc88a !important; border-color: #1cc88a !important;">
                                <x-heroicon-m-qr-code class="w-4 h-4 mr-2" />
                                Recarregar via PIX
                            </button>

                            <!-- Botão Suporte (Branco) -->
                            <a href="https://wa.me/5511999999999?text=Olá, gostaria de comprar créditos para revenda Adassoft"
                                target="_blank"
                                class="w-full sm:w-auto inline-flex items-center justify-center px-4 h-9 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600 transition-colors whitespace-nowrap">
                                <x-heroicon-m-chat-bubble-left-right class="w-4 h-4 mr-1" />
                                Suporte
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{ $this->table }}
    </div>

    {{-- Modal PIX --}}
    @if($showPixModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all"
                @click.away="$wire.set('showPixModal', false)">

                <div class="bg-primary-600 px-6 py-4 flex justify-between items-center" style="background-color: #1cc88a;">
                    <h3 class="text-white font-bold text-lg flex items-center gap-2">
                        <x-heroicon-m-qr-code class="w-6 h-6" />
                        Pagamento via PIX
                    </h3>
                    <button wire:click="$set('showPixModal', false)"
                        class="text-white hover:text-gray-200 transition-colors">
                        <x-heroicon-m-x-mark class="w-6 h-6" />
                    </button>
                </div>

                <div class="p-6 flex flex-col items-center gap-4">
                    <p class="text-center text-gray-600 dark:text-gray-300 text-sm">
                        Escaneie o QR Code abaixo com o app do seu banco para realizar o pagamento.
                    </p>

                    @if($pixQrCode)
                        <div class="p-2 border-2 border-dashed border-gray-300 rounded-lg">
                            <img src="data:image/png;base64,{{ $pixQrCode }}" alt="QR Code PIX" class="w-48 h-48">
                        </div>
                    @endif

                    <div class="w-full">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Copia e Cola</label>
                        <div class="flex gap-2">
                            <input type="text" readonly value="{{ $pixCopyPaste }}"
                                class="flex-1 text-sm border-gray-300 rounded-md bg-gray-50 text-gray-600 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-gray-300">
                            <button type="button" x-data="{ copied: false }"
                                @click="navigator.clipboard.writeText('{{ $pixCopyPaste }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                class="px-3 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md font-medium transition-colors text-sm flex items-center gap-1 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200">
                                <span x-show="!copied"><x-heroicon-m-clipboard class="w-4 h-4" /></span>
                                <span x-show="copied" class="text-green-600"><x-heroicon-m-check class="w-4 h-4" /></span>
                            </button>
                        </div>
                    </div>

                    <div
                        class="bg-blue-50 text-blue-700 p-3 rounded-md text-xs text-center dark:bg-blue-900/30 dark:text-blue-300 w-full">
                        <p class="font-bold">Aguardando pagamento...</p>
                        <p>Seu saldo será atualizado automaticamente assim que o pagamento for confirmado.</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>