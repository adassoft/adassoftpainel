<x-filament-panels::page>
    <form wire:submit.prevent="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-start">
            <x-filament::button type="submit" size="xl" class="px-10">
                <x-filament::loading-indicator wire:loading wire:target="save" class="h-5 w-5 mr-2" />
                Salvar Alterações
            </x-filament::button>
        </div>
    </form>

    <!-- Seção Dicas de SEO (Card estilo SB Admin 2) -->
    <div class="bg-white rounded shadow-sm border border-gray-100 mt-8 overflow-hidden">
        <div class="px-4 py-2 border-b border-gray-100" style="background: #fdfdfd;">
            <h2
                style="color: #17a2b8; font-weight: bold; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">
                Dicas de SEO
            </h2>
        </div>
        <div class="p-5">
            <ul class="list-disc list-inside space-y-2 text-sm text-gray-600">
                <li><strong class="text-gray-800">Títulos:</strong> Devem ser únicos e descrever o conteúdo da página.
                </li>
                <li><strong class="text-gray-800">Descrições:</strong> O texto que convence o usuário a clicar. Use
                    verbos de ação.</li>
                <li><strong class="text-gray-800">Robots.txt:</strong> Já configurado para bloquear áreas
                    administrativas. Verificar: <a href="/robots.txt" target="_blank"
                        class="text-blue-500 hover:underline">robots.txt</a></li>
                <li><strong class="text-gray-800">Sitemap:</strong> O sitemap foi configurado para apontar para <code
                        class="bg-gray-100 px-1 rounded text-pink-600">sitemap.xml</code>. Certifique-se de gerar este
                    arquivo periodicamente.</li>
            </ul>
        </div>
    </div>
</x-filament-panels::page>