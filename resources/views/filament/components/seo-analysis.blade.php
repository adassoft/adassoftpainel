<div class="mt-4 space-y-2 text-sm">
    <h4 class="font-medium text-gray-700 dark:text-gray-300">Análise de SEO</h4>

    @if(empty($focus_keyword))
        <div class="flex items-center gap-2 p-2 text-gray-600 bg-gray-50 rounded dark:bg-gray-800 dark:text-gray-400">
            <x-heroicon-o-information-circle class="w-5 h-5" />
            <span>Defina uma <strong>Palavra-chave Foco</strong> para ativar a análise.</span>
        </div>
    @else
        <div class="space-y-1">
            @foreach($analysis as $item)
                <div class="flex items-start gap-2 p-1">
                    @if($item['status'] === 'success')
                        <div class="mt-0.5 text-green-500">
                            <x-heroicon-s-check-circle class="w-5 h-5" />
                        </div>
                    @elseif($item['status'] === 'warning')
                        <div class="mt-0.5 text-orange-500">
                            <x-heroicon-s-exclamation-circle class="w-5 h-5" />
                        </div>
                    @else
                        <div class="mt-0.5 text-red-500">
                            <x-heroicon-s-x-circle class="w-5 h-5" />
                        </div>
                    @endif

                    <span class="text-gray-600 dark:text-gray-300 {{ $item['status'] !== 'success' ? 'font-medium' : '' }}">
                        {{ $item['message'] }}
                    </span>
                </div>
            @endforeach
        </div>
    @endif
</div>