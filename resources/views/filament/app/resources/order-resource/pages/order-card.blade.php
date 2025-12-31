@php
    $record = $getRecord();
    $status = strtolower($record->situacao);
    $statusEntrega = strtolower($record->status_entrega ?? '');
@endphp

<div
    class="flex flex-col bg-white dark:bg-gray-800 shadow-lg rounded-lg h-full overflow-hidden hover:shadow-xl transition-shadow duration-300">
    {{-- Header --}}
    <div
        class="bg-blue-50 px-6 py-4 border-b border-blue-100 dark:bg-gray-900/50 dark:border-gray-700 flex justify-between items-center">
        <h3 class="text-base font-bold text-blue-600 dark:text-blue-400">
            Pedido: {{ $record->id }}
        </h3>
        <span
            class="text-xs font-semibold px-2 py-1 rounded bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 shadow-sm border border-gray-100 dark:border-gray-600">
            {{ \Carbon\Carbon::parse($record->data)->format('d/m/Y') }}
        </span>
    </div>

    {{-- Body --}}
    <div class="flex-1 p-5 flex flex-col gap-3 text-sm text-gray-600 dark:text-gray-300">

        {{-- Cliente/Razão Social --}}
        <div class="flex flex-col gap-1">
            <div class="flex items-start gap-3">
                <x-heroicon-m-building-office class="w-5 h-5 text-gray-400 shrink-0" />
                <div>
                    <span class="font-bold text-gray-700 dark:text-gray-200">Razão Social:</span>
                    <span>{{ \App\Models\Company::where('cnpj', preg_replace('/\D/', '', $record->cnpj))->value('razao') ?? 'Consumidor Final' }}</span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <x-heroicon-m-identification class="w-5 h-5 text-gray-400 shrink-0" />
                <div>
                    <span class="font-bold text-gray-700 dark:text-gray-200">CNPJ:</span>
                    <span>{{ $record->cnpj }}</span>
                </div>
            </div>
        </div>

        {{-- Software --}}
        <div class="flex flex-col gap-1">
            <div class="flex items-start gap-3">
                <x-heroicon-m-cube class="w-5 h-5 text-gray-400 shrink-0" />
                <div>
                    <span class="font-bold text-gray-700 dark:text-gray-200">Software:</span>
                    <span>
                        {{ $record->software_id ? (\Illuminate\Support\Facades\DB::table('softwares')->where('id', $record->software_id)->value('nome_software') ?? 'Não identificado') : 'Não identificado' }}
                    </span>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <x-heroicon-m-square-3-stack-3d class="w-5 h-5 text-gray-400 shrink-0" />
                <div>
                    <span class="font-bold text-gray-700 dark:text-gray-200">Plano:</span>
                    <span>
                        {{ $record->plano_id ? (\Illuminate\Support\Facades\DB::table('planos')->where('id', $record->plano_id)->value('nome_plano') ?? 'Padrão') : 'Padrão' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Transação --}}
        <div class="flex flex-col">
            <div class="flex items-center gap-3">
                <x-heroicon-m-qr-code class="w-5 h-5 text-gray-400 shrink-0" />
                <span class="font-bold text-gray-700 dark:text-gray-200">Transação:</span>
            </div>
            <div class="pl-8 text-gray-500 font-mono text-xs break-all truncate" title="{{ $record->cod_transacao }}">
                {{ $record->cod_transacao }}
            </div>
        </div>

        {{-- Recorrência --}}
        <div class="flex items-center gap-3">
            <x-heroicon-m-arrow-path class="w-5 h-5 text-gray-400 shrink-0" />
            <div>
                <span class="font-bold text-gray-700 dark:text-gray-200">Ciclo:</span>
                <span>{{ $record->recorrencia }} {{ $record->recorrencia == 1 ? 'Mês' : 'Meses' }}</span>
            </div>
        </div>

        {{-- Valor --}}
        <div class="flex items-center gap-3">
            <x-heroicon-m-banknotes class="w-5 h-5 text-gray-400 shrink-0" />
            <div>
                <span class="font-bold text-gray-700 dark:text-gray-200">Valor:</span>
                <span class="font-bold text-green-600">R$ {{ number_format($record->valor, 2, ',', '.') }}</span>
            </div>
        </div>

        {{-- Status --}}
        <div class="flex items-center gap-3">
            <x-heroicon-m-information-circle class="w-5 h-5 text-gray-400 shrink-0" />
            <div class="flex items-center gap-2">
                <span class="font-bold text-gray-700 dark:text-gray-200">Status:</span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wide
                    @if(in_array($status, ['pago', 'aprovado'])) bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400
                    @elseif($status === 'cancelado') bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400
                    @else bg-yellow-100 text-yellow-700 dark:bg-yellow-500/20 dark:text-yellow-400
                    @endif">
                    {{ ucwords(str_replace('_', ' ', $record->situacao)) }}
                </span>
            </div>
        </div>

        {{-- Entrega --}}
        <div class="flex items-center gap-3">
            <x-heroicon-m-truck class="w-5 h-5 text-gray-400 shrink-0" />
            <div class="flex items-center gap-1">
                <span class="font-bold text-gray-700 dark:text-gray-200">Entrega:</span>
                <span class="capitalize">{{ $record->status_entrega ?? 'Pendente' }}</span>
            </div>
        </div>

    </div>

    {{-- Footer Actions --}}
    <div
        class="p-4 bg-gray-50/50 border-t border-gray-100 flex flex-col items-center gap-3 dark:bg-gray-800 dark:border-gray-700">

        @if(in_array($status, ['pendente', 'aguardando']))
            <div class="grid grid-cols-2 gap-3 w-full">
                {{-- Botão Pagar --}}
                <button wire:click="mountTableAction('pagar', '{{ $record->id }}')"
                    class="w-full text-white font-bold py-2.5 px-4 rounded-lg shadow-sm hover:shadow transition-all duration-200 ease-in-out flex items-center justify-center gap-2 transform hover:-translate-y-0.5"
                    style="background-color: #06b6d4; color: white;"> {{-- Cyan 500 --}}
                    <x-heroicon-m-qr-code class="w-5 h-5" /> Pagar PIX
                </button>

                {{-- Botão Cancelar --}}
                <button wire:click="mountTableAction('cancelar', '{{ $record->id }}')"
                    class="w-full text-white font-bold py-2.5 px-4 rounded-lg shadow-sm hover:shadow transition-all duration-200 ease-in-out flex items-center justify-center gap-2 transform hover:-translate-y-0.5"
                    style="background-color: #ef4444; color: white;"> {{-- Red 500 --}}
                    <x-heroicon-m-x-circle class="w-5 h-5" /> Cancelar
                </button>
            </div>

        @elseif(in_array($status, ['pago', 'aprovado']))
            <div
                class="w-full bg-green-100 text-green-700 px-3 py-2.5 rounded-lg text-center text-sm font-bold border border-green-200 flex items-center justify-center gap-2">
                <x-heroicon-m-check-circle class="w-5 h-5" />
                Pedido Concluído
            </div>

        @elseif($status === 'cancelado')
            <div
                class="w-full bg-gray-100 text-gray-500 px-3 py-2.5 rounded-lg text-center text-sm font-bold border border-gray-200">
                Cancelado
            </div>
        @endif
    </div>
</div>