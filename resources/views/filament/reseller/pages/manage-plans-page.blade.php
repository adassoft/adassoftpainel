<x-filament-panels::page>
    <div class="space-y-6">
        <div class="p-4 bg-blue-50 text-blue-800 rounded-lg flex items-start gap-3 border border-blue-100">
            <svg class="w-6 h-6 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <h4 class="font-bold">Como funciona?</h4>
                <p class="text-sm mt-1">
                    Selecione os planos que deseja vender ativando a chave na coluna <strong>Ativo</strong>.
                    Defina o preço final para seu cliente na coluna <strong>Seu Preço de Venda</strong>.
                    Se desmarcar um plano, ele não aparecerá para seus clientes na hora da renovação/compra.
                </p>
            </div>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>