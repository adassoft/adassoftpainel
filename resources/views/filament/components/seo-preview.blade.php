<div x-data="{
    title: $wire.entangle('{{ $titleStatePath }}'),
    description: $wire.entangle('{{ $descriptionStatePath }}'),
    url: '{{ config('app.url') }}/...',
}" class="p-4 bg-white rounded-lg border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
    <h3 class="text-sm font-medium text-gray-500 mb-2">Pré-visualização Google (SERP)</h3>

    <div class="font-sans max-w-xl">
        <div class="flex items-center gap-1 mb-1">
            <div class="bg-gray-100 dark:bg-gray-700 p-1 rounded-full">
                <img src="{{ asset('favicon.svg') }}" class="w-4 h-4 object-contain">
            </div>
            <div class="flex flex-col leading-none">
                <span class="text-xs text-gray-800 dark:text-gray-200 font-medium">{{ config('app.name') }}</span>
                <span class="text-[10px] text-gray-500" x-text="url"></span>
            </div>
        </div>

        <h3 class="text-xl text-[#1a0dab] dark:text-[#8ab4f8] font-medium hover:underline cursor-pointer truncate"
            x-text="title || 'Título da Página'">
        </h3>

        <p class="text-sm text-[#4d5156] dark:text-gray-400 mt-1 line-clamp-2"
            x-text="description || 'Breve descrição do conteúdo da página que aparecerá nos resultados de busca...'">
        </p>
    </div>
</div>