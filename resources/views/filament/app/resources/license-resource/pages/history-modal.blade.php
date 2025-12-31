<div class="space-y-4">
    <div class="overflow-x-auto">
        <table
            class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700 rounded-lg">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">Data Renovação</th>
                    <th scope="col" class="px-6 py-3">Ciclo</th>
                    <th scope="col" class="px-6 py-3">Valor</th>
                    <th scope="col" class="px-6 py-3">Transação</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ \Carbon\Carbon::parse($order->data)->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $order->recorrencia ?? 1 }} Meses
                        </td>
                        <td class="px-6 py-4 text-green-600 font-bold">
                            R$ {{ number_format($order->valor, 2, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 font-mono text-xs">
                            {{ $order->cod_transacao }}
                        </td>
                    </tr>
                @empty
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <td colspan="4" class="px-6 py-4 text-center">
                            Nenhum histórico de renovação encontrado via pedidos (web).
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>