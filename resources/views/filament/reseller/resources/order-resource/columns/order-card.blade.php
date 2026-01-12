@php
    $record = $getRecord();
    $status = strtolower($record->status ?? '');
    $statusEntrega = strtolower($record->status_entrega ?? '');
@endphp

<div class="flex flex-col bg-white dark:bg-gray-800 shadow-lg rounded-lg h-full overflow-hidden">
    {{-- Header --}}
    <div class="bg-blue-50 px-6 py-4 border-b border-blue-100 dark:bg-gray-900/50 dark:border-gray-700">
        <h3 class="text-base font-bold text-blue-600 dark:text-blue-400">
            Pedido: {{ $record->id }}
        </h3>
    </div>

    {{-- Body --}}
    <div class="flex-1 p-6 flex flex-col gap-3 text-sm text-gray-600 dark:text-gray-300">

        {{-- Cliente --}}
        <div class="flex flex-col gap-1">
            <div class="flex items-start gap-3">
                <x-heroicon-m-building-office class="w-5 h-5 text-gray-400 shrink-0" />
                <div>
                    <span class="font-bold text-gray-700 dark:text-gray-200">Razão Social:</span>
                    <span>{{ $record->user->empresa->razao ?? 'N/A' }}</span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <x-heroicon-m-identification class="w-5 h-5 text-gray-400 shrink-0" />
                <div>
                    @php
                        $docValue = preg_replace('/\D/', '', $record->user->empresa->cnpj ?? $record->user->cpf ?? $record->user->cnpj ?? '');
                        $docLabel = strlen($docValue) > 11 ? 'CNPJ:' : 'CPF:';
                        $docFormatted = $docValue;
                        if (strlen($docValue) <= 11 && strlen($docValue) > 0) {
                            $docFormatted = substr($docValue, 0, 3) . '.***.***-' . substr($docValue, -2);
                        } elseif (strlen($docValue) > 11) {
                            $docFormatted = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $docValue);
                        } else {
                            $docFormatted = 'N/A';
                        }
                    @endphp
                    <span class="font-bold text-gray-700 dark:text-gray-200">{{ $docLabel }}</span>
                    <span>
                        {{ $docFormatted }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Software --}}
        <div class="flex flex-col gap-1">
            <div class="flex items-start gap-3">
                <x-heroicon-m-cube class="w-5 h-5 text-gray-400 shrink-0" />
                <div>
                    <span class="font-bold text-gray-700 dark:text-gray-200">Software:</span>
                    <span>{{ $record->plan->software->nome_software ?? 'Não identificado' }}</span>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <x-heroicon-m-square-3-stack-3d class="w-5 h-5 text-gray-400 shrink-0" />
                <div>
                    <span class="font-bold text-gray-700 dark:text-gray-200">Plano:</span>
                    <span>{{ $record->plan->nome_plano ?? 'Padrão' }}</span>
                </div>
            </div>
        </div>

        {{-- Data --}}
        <div class="flex items-center gap-3">
            <x-heroicon-m-calendar class="w-5 h-5 text-gray-400 shrink-0" />
            <div>
                <span class="font-bold text-gray-700 dark:text-gray-200">Data:</span>
                <span>{{ \Carbon\Carbon::parse($record->data)->format('d/m/Y') }}</span>
            </div>
        </div>

        {{-- Transação --}}
        <div class="flex flex-col">
            <div class="flex items-center gap-3">
                <x-heroicon-m-qr-code class="w-5 h-5 text-gray-400 shrink-0" />
                <span class="font-bold text-gray-700 dark:text-gray-200">Transação:</span>
            </div>
            <div class="pl-8 text-gray-500 font-mono text-xs break-all">
                {{ $record->external_reference ?? $record->asaas_payment_id ?? '-' }}
            </div>
        </div>

        {{-- Recorrência --}}
        <div class="flex items-center gap-3">
            <x-heroicon-m-arrow-path class="w-5 h-5 text-gray-400 shrink-0" />
            <div>
                <span class="font-bold text-gray-700 dark:text-gray-200">Ciclo:</span>
                @php
                    $recorrencia = $record->recorrencia ?? $record->plan->recorrencia ?? '';
                @endphp
                <span>{{ $recorrencia }} {{ $recorrencia == 1 ? 'Mês' : 'Meses' }}</span>
            </div>
        </div>

        {{-- Valor --}}
        <div class="flex items-center gap-3">
            <x-heroicon-m-banknotes class="w-5 h-5 text-gray-400 shrink-0" />
            <div>
                <span class="font-bold text-gray-700 dark:text-gray-200">Valor:</span>
                <span>R$ {{ number_format($record->valor, 2, ',', '.') }}</span>
            </div>
        </div>

        {{-- Status --}}
        <div class="flex items-center gap-3">
            <x-heroicon-m-information-circle class="w-5 h-5 text-gray-400 shrink-0" />
            <div class="flex items-center gap-2">
                <span class="font-bold text-gray-700 dark:text-gray-200">Status:</span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wide
                    @if(in_array($status, ['pago', 'paid', 'approved', 'completed'])) bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400
                    @elseif(in_array($status, ['cancelado', 'cancelled', 'refused'])) bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400
                    @else bg-yellow-100 text-yellow-700 dark:bg-yellow-500/20 dark:text-yellow-400
                    @endif">
                    @php
                        $statusLabel = match ($status) {
                            'pago', 'paid', 'approved', 'completed' => 'Pago',
                            'cancelado', 'cancelled', 'refused' => 'Cancelado',
                            'pendente', 'pending' => 'Pendente',
                            default => $record->status ?? 'Pendente'
                        };
                    @endphp
                    {{ ucfirst($statusLabel) }}
                </span>
            </div>
        </div>

        {{-- Entrega --}}
        <div class="flex items-center gap-3">
            <x-heroicon-m-truck class="w-5 h-5 text-gray-400 shrink-0" />
            <div class="flex items-center gap-1">
                <span class="font-bold text-gray-700 dark:text-gray-200">Entrega:</span>
                <span class="
                    @if($statusEntrega === 'entregue') text-green-600 font-medium
                    @elseif($statusEntrega === 'pendente_saldo') text-red-600 font-bold bg-red-50 px-2 py-0.5 rounded text-xs uppercase
                    @else text-orange-500
                    @endif
                ">
                    {{ ucwords(str_replace('_', ' ', $record->status_entrega ?? 'Pendente')) }}
                </span>
            </div>
        </div>

    </div>

    {{-- Footer Actions --}}
    <div
        class="p-4 bg-gray-50/50 border-t border-gray-100 flex flex-col items-center gap-3 dark:bg-gray-800 dark:border-gray-700">

        @if($status === 'pago' && $statusEntrega !== 'entregue')
            <button wire:click="mountTableAction('liberar', '{{ $record->id }}')"
                class="w-full text-white font-bold py-2.5 px-4 rounded-lg shadow-sm hover:shadow transition-all duration-200 ease-in-out flex items-center justify-center gap-2 transform hover:-translate-y-0.5"
                style="background-color: #06b6d4; color: white;">
                <x-heroicon-m-key class="w-5 h-5" /> Liberar Licença
            </button>
        @elseif($status === 'pago' && $statusEntrega === 'entregue')
            <button disabled
                class="w-full text-white font-bold py-2.5 px-4 rounded-lg shadow-sm opacity-80 cursor-not-allowed flex items-center justify-center gap-2"
                style="background-color: #10b981; color: white;"> {{-- Emerald 500 --}}
                <x-heroicon-m-check-circle class="w-5 h-5" /> Licença Liberada
            </button>
        @endif

        @if($status !== 'pago' && $status !== 'cancelado')
            <button wire:click="mountTableAction('receber', '{{ $record->id }}')"
                class="w-full text-white font-bold py-2.5 px-4 rounded-lg shadow-sm hover:shadow transition-all duration-200 ease-in-out flex items-center justify-center gap-2 transform hover:-translate-y-0.5"
                style="background-color: #ef4444; color: white;">
                <x-heroicon-m-currency-dollar class="w-5 h-5" /> Receber (Baixar)
            </button>
        @endif
    </div>
</div>