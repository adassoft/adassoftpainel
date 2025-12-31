<div class="p-4 bg-gray-50 border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-700">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Token Assinado (Shield V2)</label>
    <div class="relative">
        <textarea id="license-token-textarea" readonly
            class="w-full h-48 font-mono text-xs bg-white border border-gray-300 rounded p-3 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200">{{ $token }}</textarea>

        <button type="button" onclick="copyTokenToClipboard()"
            class="absolute top-2 right-2 bg-primary-600 text-white p-1.5 rounded-md hover:bg-primary-700 transition shadow-sm"
            title="Copiar token">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                </path>
            </svg>
        </button>
    </div>
    <p class="mt-2 text-xs text-gray-500">Este token é utilizado para ativações via API ou Offline nos softwares
        protegidos.</p>
</div>

<script>
    function copyTokenToClipboard() {
        const textarea = document.getElementById('license-token-textarea');
        textarea.select();
        document.execCommand('copy');

        // Feedback visual opcional (notificação Filament é melhor, mas aqui é simples)
        alert('Token copiado para a área de transferência!');
    }
</script>