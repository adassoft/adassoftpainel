<div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
    @foreach ($this->getCachedStats() as $stat)
        @php
            $color = $stat->getColor() ?? 'gray';

            // Cores exatas do template original customizadas
            $hexColor = match ($color) {
                'info' => '#4e73df',     // Azul
                'success' => '#1cc88a',  // Verde
                'warning' => '#f6c23e',  // Amarelo
                'danger' => '#e74a3b',   // Vermelho
                default => '#36b9cc'     // Ciano
            };

            $icon = $stat->getDescriptionIcon();
        @endphp

        <!-- Card Container -->
        <div class="relative bg-white rounded shadow-sm overflow-hidden"
            style="border-left: 0.25rem solid {{ $hexColor }}; padding: 1.25rem; height: 100%;">

            <div class="flex items-center justify-between h-full">
                <!-- Coluna Texto (Esquerda) -->
                <div class="flex-1 mr-2">
                    <div class="text-xs font-bold uppercase mb-1 truncate"
                        style="color: {{ $hexColor }}; font-size: 0.75rem; letter-spacing: 0.05em; line-height: 1.2;">
                        {{ $stat->getLabel() }}
                    </div>
                    <div class="h5 mb-0 font-bold text-gray-800"
                        style="font-size: 1.25rem; color: #5a5c69; line-height: 1.2;">
                        {{ $stat->getValue() }}
                    </div>
                </div>

                <!-- Coluna Ícone (Direita) -->
                @if ($icon)
                    <div class="flex-shrink-0">
                        <!-- Ícone tamanho fa-2x equivalente (~32px/2rem) e cor gray-300 (#dddfeb no original, mas vou escurecer levemente para #d1d3e2 para visibilidade) -->
                        @svg($icon, 'text-gray-300', ['style' => 'width: 2.5rem; height: 2.5rem; color: #d1d3e2;'])
                    </div>
                @endif
            </div>
        </div>
    @endforeach
</div>