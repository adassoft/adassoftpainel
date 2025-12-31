<x-filament-panels::page>
    <form wire:submit="submit">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit" size="lg">
                Gerar Token Offline
            </x-filament::button>
        </div>
    </form>

    @if($generatedToken)
        <x-filament::section class="mt-6" icon="heroicon-o-check-circle" icon-color="success">
            <x-slot name="heading">
                Token Offline Gerado com Sucesso
            </x-slot>

            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Envie o código abaixo ao cliente. Ele deve colar este token no campo de ativação do software.
            </p>

            <div class="relative group">
                <textarea readonly
                    class="w-full h-40 font-mono text-xs bg-gray-50 border border-gray-200 rounded-lg p-3 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300"
                    id="offline-token-textarea">{{ $generatedToken }}</textarea>

                <x-filament::button color="gray" size="sm" class="absolute top-2 right-2" onclick="copyOfflineToken()">
                    Copiar
                </x-filament::button>
            </div>

            @if($tokenPayload)
                <div class="mt-6 border-t pt-4 dark:border-gray-700">
                    <h4 class="text-sm font-bold mb-2">Detalhes da Ativação:</h4>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-2 text-xs">
                        <div class="flex justify-between border-b pb-1 dark:border-gray-800">
                            <dt class="text-gray-500 font-medium">Serial:</dt>
                            <dd class="font-mono">{{ $tokenPayload['serial'] }}</dd>
                        </div>
                        <div class="flex justify-between border-b pb-1 dark:border-gray-800">
                            <dt class="text-gray-500 font-medium">Software:</dt>
                            <dd>{{ $tokenPayload['software'] }}</dd>
                        </div>
                        <div class="flex justify-between border-b pb-1 dark:border-gray-800">
                            <dt class="text-gray-500 font-medium">Instalação ID:</dt>
                            <dd class="font-mono">{{ $tokenPayload['instalacao_id'] }}</dd>
                        </div>
                        <div class="flex justify-between border-b pb-1 dark:border-gray-800">
                            <dt class="text-gray-500 font-medium">Validade do Token:</dt>
                            <dd>{{ \Carbon\Carbon::parse($tokenPayload['expira_em'])->format('d/m/Y H:i') }} (3 dias)</dd>
                        </div>
                    </dl>
                </div>
            @endif
        </x-filament::section>

        <script>
            function copyOfflineToken() {
                const textarea = document.getElementById('offline-token-textarea');
                textarea.select();
                document.execCommand('copy');

                new FilamentNotification()
                    .title('Token copiado!')
                    .success()
                    .send();
            }
        </script>
    @endif
</x-filament-panels::page>