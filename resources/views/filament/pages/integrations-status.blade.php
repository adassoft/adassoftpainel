<x-filament-panels::page>
    <div class="grid gap-6">
        <!-- Feed INFO -->
        <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <h2 class="text-lg font-bold mb-4">Google Shopping / Meta Ads</h2>
            <p class="text-sm text-gray-500 mb-2">Utilize a URL abaixo para cadastrar seu catálogo no Google Merchant
                Center ou Gerenciador de Negócios do Facebook.</p>

            <div class="flex items-center gap-2">
                <input type="text" value="{{ $feedUrl }}" readonly
                    class="w-full p-2 bg-gray-50 border rounded text-sm font-mono text-gray-600 select-all focus:ring-2 focus:ring-primary-500">
                <a href="{{ $feedUrl }}" target="_blank"
                    class="px-4 py-2 bg-primary-600 text-white rounded hover:bg-primary-700 text-sm font-bold">
                    Testar
                </a>
            </div>
            <p class="text-xs text-gray-400 mt-2">Atualizado automaticamente a cada solicitação.</p>
        </div>

        <!-- Diagnostic Table -->
        <div
            class="bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="font-bold">Diagnóstico de Produtos</h3>
            </div>

            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 dark:bg-gray-900 text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Software</th>
                        <th class="px-4 py-3">Status XML</th>
                        <th class="px-4 py-3">Observações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($softwares as $soft)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 font-medium">{{ $soft['name'] }}</td>
                            <td class="px-4 py-3">
                                @if($soft['status'] == 'OK')
                                    <span class="px-2 py-1 rounded bg-green-100 text-green-800 text-xs font-bold">Pronto</span>
                                @else
                                    <span
                                        class="px-2 py-1 rounded bg-yellow-100 text-yellow-800 text-xs font-bold">Atenção</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500">
                                @if(empty($soft['warnings']))
                                    <span class="text-green-600 flex items-center gap-1">
                                        <x-heroicon-o-check-circle class="w-4 h-4" /> Tudo certo
                                    </span>
                                @else
                                    <div class="flex flex-col gap-1">
                                        @foreach($soft['warnings'] as $warning)
                                            <span class="text-red-500 text-xs flex items-center gap-1">
                                                <x-heroicon-o-exclamation-triangle class="w-3 h-3" /> {{ $warning }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>