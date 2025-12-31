<div class="flex flex-col items-center justify-center p-6 space-y-4 text-center">
    <div class="text-red-500 bg-red-100 p-3 rounded-full dark:bg-red-900/30 dark:text-red-400">
        <x-heroicon-o-exclamation-triangle class="w-8 h-8" />
    </div>

    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Não foi possível gerar o pagamento</h3>

    <p class="text-sm text-gray-500 dark:text-gray-400">
        {{ $message ?? 'Ocorreu um erro inesperado. Entre em contato com o suporte.' }}
    </p>
</div>