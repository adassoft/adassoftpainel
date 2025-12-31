<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($gateways as $key => $g)
            @php
                $record = $g['record'];
                $isConfigured = !is_null($record);
                $isActive = $record?->active ?? false;
                $isProd = ($record?->producao ?? 'n') === 's';
                $headerColor = $g['slug'] === 'mercadopago' ? '#009ee3' : '#0030b9'; // Cores aproximadas das marcas
            @endphp

            <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden flex flex-col h-full"
                style="box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);">
                <!-- Header -->
                <div class="text-white flex justify-between items-center"
                    style="padding: 0.75rem 1.25rem; background-color: {{ $headerColor }}">
                    <h3 class="font-bold text-lg">{{ $g['name'] }}</h3>
                    @if($isConfigured)
                        <span
                            class="px-2 py-1 rounded text-xs font-bold uppercase {{ $isProd ? 'bg-green-500 text-white' : 'bg-yellow-400 text-gray-800' }}">
                            {{ $isProd ? 'Produção' : 'Teste' }}
                        </span>
                    @endif
                </div>

                <!-- Body -->
                <div class="flex-grow text-sm text-gray-700 space-y-2" style="padding: 1.25rem;">
                    <p>
                        <strong>ID:</strong> {{ $record?->id ?? '—' }}
                    </p>
                    <p>
                        <strong>Criado Em:</strong> {{ $record?->created_at?->format('Y-m-d H:i:s') ?? '—' }}
                    </p>
                    <p>
                        <strong>Atualizado Em:</strong> {{ $record?->updated_at?->format('Y-m-d H:i:s') ?? '—' }}
                    </p>

                    @if($g['slug'] === 'mercadopago')
                        <div>
                            <strong>Access Token:</strong>
                            <span
                                class="font-mono text-gray-500 break-all">{{ $record?->access_token ? substr($record->access_token, 0, 8) . '********' : '(não configurado)' }}</span>
                        </div>
                        <div>
                            <strong>Public Key:</strong>
                            <span
                                class="font-mono text-gray-500 break-all">{{ $record?->public_key ? substr($record->public_key, 0, 8) . '********' : '(não configurado)' }}</span>
                        </div>
                        <div>
                            <strong>Client ID:</strong>
                            <span
                                class="font-mono text-gray-500 break-all">{{ $record?->client_id ? substr($record->client_id, 0, 8) . '********' : '(não configurado)' }}</span>
                        </div>
                    @elseif($g['slug'] === 'asaas')
                        <div>
                            <strong>Access Token:</strong>
                            <span
                                class="font-mono text-gray-500 break-all">{{ $record?->access_token ? substr($record->access_token, 0, 8) . '********' : '(não configurado)' }}</span>
                        </div>
                        <div>
                            <strong>Wallet ID:</strong>
                            <span
                                class="font-mono text-gray-500 break-all">{{ $record?->wallet_id ? substr($record->wallet_id, 0, 8) . '********' : '(não configurado)' }}</span>
                        </div>
                    @endif
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 border-t border-gray-200 flex items-center justify-between" style="padding: 1rem 1.25rem;">
                    <!-- Status Toggle Mockup (Action Button acting as Toggle) -->
                    <div class="flex items-center space-x-2">
                        @if($isConfigured)
                            <button wire:click="mountAction('toggleStatus', { id: {{ $record->id }} })"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $isActive ? 'bg-green-500' : 'bg-gray-200' }}"
                                role="switch" aria-checked="{{ $isActive ? 'true' : 'false' }}">
                                <span
                                    class="translate-x-0 pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $isActive ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                            <span class="text-sm font-medium {{ $isActive ? 'text-gray-900' : 'text-gray-400' }}">
                                {{ $isActive ? 'Ligado' : 'Desligado' }}
                            </span>
                        @else
                            <span class="text-gray-300 text-sm">Não configurado</span>
                        @endif
                    </div>

                    <!-- Action Button -->
                    <x-filament::button size="sm" :color="$isConfigured ? 'primary' : 'success'" :icon="$isConfigured ? 'heroicon-m-pencil-square' : 'heroicon-m-plus'"
                        wire:click="mountAction('configureGateway', { gateway: '{{ $g['slug'] }}', record_id: {{ $record?->id ?? 'null' }} })">
                        {{ $isConfigured ? 'Editar' : 'Cadastrar' }}
                    </x-filament::button>
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>