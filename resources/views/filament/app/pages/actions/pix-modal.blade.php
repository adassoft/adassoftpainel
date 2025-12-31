<div class="flex flex-col items-center justify-center p-4 space-y-4">
    <div class="text-center">
        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Pagamento via PIX</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Escaneie o QR Code ou copie o código abaixo</p>
    </div>

    @if($qrCode)
        <div class="p-2 border-2 border-dashed border-gray-300 rounded-lg bg-white">
            <img src="data:image/png;base64,{{ $qrCode }}" alt="QR Code PIX" class="w-48 h-48">
        </div>
    @endif

    <div class="w-full">
        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Pix Copia e Cola</label>
        <div class="flex gap-2">
            <input type="text" readonly value="{{ $copyPaste }}"
                class="flex-1 text-sm border-gray-300 rounded-md bg-gray-50 text-gray-600 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-gray-300 p-2">
            <button type="button" x-data="{ copied: false }"
                @click="navigator.clipboard.writeText('{{ $copyPaste }}'); copied = true; setTimeout(() => copied = false, 2000)"
                class="px-3 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md font-medium transition-colors text-sm flex items-center gap-1 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200">
                <span x-show="!copied"><x-heroicon-m-clipboard class="w-5 h-5" /></span>
                <span x-show="copied" class="text-green-600"><x-heroicon-m-check class="w-5 h-5" /></span>
            </button>
        </div>
    </div>

    <div
        class="bg-blue-50 text-blue-700 p-3 rounded-md text-xs text-center dark:bg-blue-900/30 dark:text-blue-300 w-full">
        <p class="font-bold">Valor: R$ {{ number_format($valor, 2, ',', '.') }}</p>
        <p>A confirmação pode levar alguns instantes.</p>
    </div>
</div>