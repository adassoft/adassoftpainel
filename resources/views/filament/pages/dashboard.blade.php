<x-filament-panels::page>

    <!-- Widgets de Métricas e Gráficos -->
    <div class="space-y-6">
        @livewire(\App\Filament\Widgets\DashboardStatsOverview::class)

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @livewire(\App\Filament\Widgets\RevenueChart::class)
            @livewire(\App\Filament\Widgets\GeographicDistributionChart::class)
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <div class="lg:col-span-1">
                @livewire(\App\Filament\Widgets\TopSoftwaresChart::class)
            </div>
            <div class="lg:col-span-3">
                @livewire(\App\Filament\Widgets\ExpiringLicensesWidget::class)
            </div>
        </div>

        @livewire(\App\Filament\Widgets\DashboardNews::class)
    </div>

    <!-- Seção de Filtros (Compacta e Horizontal - Fix com Inline Styles) -->
    <div class="bg-white rounded shadow-sm border border-gray-100 mt-6 overflow-hidden">
        <div class="px-4 py-1.5 border-b border-gray-100" style="background: #fdfdfd;">
            <h2
                style="color: #2563eb; font-weight: bold; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">
                Filtros (Eventos)</h2>
        </div>
        <div style="padding: 1.25rem;">
            <div style="display: flex; flex-wrap: wrap; gap: 1.5rem; align-items: flex-start;">
                <!-- Software -->
                <div style="flex: 1; min-width: 150px;">
                    <label
                        style="display: block; font-size: 10px; color: #9ca3af; margin-bottom: 3px; font-weight: 500;">Software</label>
                    <select wire:model.live="software_id"
                        style="width: 100%; border: 1px solid #e5e7eb; border-radius: 4px; color: #4b5563; font-size: 11px; padding: 4px 8px; height: 32px; outline: none; background: white;">
                        <option value="">Todos</option>
                        @foreach($this->softwares as $id => $nome)
                            <option value="{{ $id }}">{{ $nome }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Empresa -->
                <div style="flex: 2; min-width: 250px;">
                    <label
                        style="display: block; font-size: 10px; color: #9ca3af; margin-bottom: 3px; font-weight: 500;">Empresa</label>
                    <input type="text" wire:model="empresa_search" placeholder="Digite razão social ou CNPJ"
                        style="width: 100%; border: 1px solid #e5e7eb; border-radius: 4px; color: #4b5563; font-size: 11px; padding: 4px 8px; height: 32px; outline: none; background: white;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 4px;">
                        <p style="font-size: 9px; color: #9ca3af; font-style: italic;">Nenhuma empresa selecionada</p>
                        <div style="display: flex; gap: 4px;">
                            <button type="button" class="px-2 py-0.5"
                                style="font-size: 9px; border: 1px solid #e5e7eb; border-radius: 3px; color: #6b7280; background: white; cursor: pointer;">Limpar</button>
                            <button type="button" class="px-2 py-0.5"
                                style="font-size: 9px; background: #3b82f6; color: white; border-radius: 3px; border: none; box-shadow: 0 1px 2px rgba(0,0,0,0.05); cursor: pointer;">Buscar</button>
                        </div>
                    </div>
                </div>

                <!-- Dias -->
                <div style="width: 80px;">
                    <label
                        style="display: block; font-size: 10px; color: #9ca3af; margin-bottom: 3px; font-weight: 500;">Dias</label>
                    <input type="number" wire:model="dias"
                        style="width: 100%; border: 1px solid #e5e7eb; border-radius: 4px; color: #4b5563; font-size: 11px; padding: 4px 8px; height: 32px; text-align: center; outline: none; background: white;">
                </div>

                <!-- Botão Filtrar -->
                <div style="align-self: flex-end; padding-bottom: 2px;">
                    <button wire:click="submitFilters"
                        style="background: #2563eb; color: white; font-weight: bold; padding: 8px 45px; border-radius: 6px; font-size: 12px; border: none; cursor: pointer; transition: background 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                        Filtrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela Eventos Por Dia -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 mt-6 overflow-hidden">
        <div class="px-4 py-2 border-b border-gray-100" style="background: #fdfdfd;">
            <h2
                style="color: #2563eb; font-weight: bold; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">
                Eventos por dia (Log de Validação)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-center text-gray-500">
                <thead class="text-xs text-gray-400 uppercase bg-white border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-3 font-bold text-gray-600">Dia</th>
                        <th class="px-6 py-3 font-bold text-gray-600">Evento</th>
                        <th class="px-6 py-3 font-bold text-gray-600">Total</th>
                        <th class="px-6 py-3 font-bold text-gray-600">Validações OK</th>
                        <th class="px-6 py-3 font-bold text-gray-600">Validações Falha</th>
                        <th class="px-6 py-3 font-bold text-gray-600">Pagos</th>
                        <th class="px-6 py-3 font-bold text-gray-600">Aguardando</th>
                        <th class="px-6 py-3 font-bold text-gray-600">Soma Valor</th>
                        <th class="px-6 py-3 font-bold text-gray-600">Soma Pago</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($eventosPorDia as $ev)
                        <tr class="hover:bg-gray-50 border-b border-gray-50">
                            <td class="px-6 py-4 font-medium">{{ \Carbon\Carbon::parse($ev->data)->format('d/m/Y') }}</td>
                            <td class="px-6 py-4">-</td>
                            <td class="px-6 py-4">{{ $ev->total }}</td>
                            <td class="px-6 py-4 text-green-500 font-bold">{{ $ev->ok }}</td>
                            <td class="px-6 py-4 text-red-500">{{ $ev->falha }}</td>
                            <td class="px-6 py-4">-</td>
                            <td class="px-6 py-4">-</td>
                            <td class="px-6 py-4">-</td>
                            <td class="px-6 py-4">-</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-gray-400 bg-gray-50">
                                Nenhum registro encontrado para o período filtrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <style>
        .legacy-grid-4 {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 0.75rem;
        }

        @media (min-width: 768px) {
            .legacy-grid-4 {
                grid-template-columns: repeat(4, 1fr);
            }
        }
    </style>

    <!-- Grid Últimos Clientes (Estilo Card igual ao legado) -->
    <div class="bg-white rounded shadow-sm border border-gray-100 mt-6 overflow-hidden mb-6">
        <div class="px-4 py-2 border-b border-gray-100" style="background: #fdfdfd;">
            <h2
                style="color: #2563eb; font-weight: bold; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">
                Últimos 4 Clientes
            </h2>
        </div>

        <div style="padding: 1.25rem;">
            <div class="legacy-grid-4">
                @foreach($ultimosClientes as $cli)
                    <div
                        style="background: white; border: 1px solid #e3e6f0; border-radius: 0.35rem; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);">
                        <!-- Conteúdo do Card -->
                        <div style="padding: 1.25rem; flex: 1;">
                            <h3
                                style="color: #4e73df; font-size: 1.1rem; margin-bottom: 0.75rem; font-weight: 700; font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
                                {{ $cli->razao }}
                            </h3>

                            <div
                                style="font-size: 0.85rem; color: #858796; line-height: 1.6; font-family: 'Nunito', sans-serif;">
                                <p style="margin: 0; color: #858796;"><span
                                        style="font-weight: 800; color: #5a5c69;">ID:</span> {{ $cli->codigo }}</p>
                                <p style="margin: 0; color: #858796;"><span
                                        style="font-weight: 800; color: #5a5c69;">CNPJ:</span> {{ $cli->cnpj }}</p>
                                <p style="margin: 0; color: #858796;"><span
                                        style="font-weight: 800; color: #5a5c69;">Validade:</span>
                                    {{ $cli->validade_licenca ? \Carbon\Carbon::parse($cli->validade_licenca)->format('d/m/Y') : 'N/A' }}
                                </p>
                                <p style="margin: 0; color: #858796;"><span
                                        style="font-weight: 800; color: #5a5c69;">UF:</span> {{ $cli->uf ?: 'N/A' }}</p>
                            </div>
                        </div>

                        <!-- Rodapé com Botão -->
                        <div style="padding: 0.75rem 1.25rem; background: #fdfdfd; border-top: 1px solid #e3e6f0;">
                            <a href="{{ \App\Filament\Resources\CompanyResource::getUrl('edit', ['record' => $cli->codigo]) }}"
                                style="display: block; background: #4e73df; color: white; text-align: center; padding: 0.5rem; border-radius: 4px; font-size: 0.85rem; font-weight: 400; text-decoration: none; border: 1px solid #4e73df; transition: all 0.3s;">
                                Ver Detalhes
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

</x-filament-panels::page>