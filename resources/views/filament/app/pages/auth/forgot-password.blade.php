<x-filament-panels::page.simple>
    @if ($step === 1)
        {{-- Passo 1: Solicitar Código --}}
        <x-filament-panels::form wire:submit="requestToken">
            {{ $this->form }}

            <x-filament::button type="submit" class="w-full">
                Enviar Código
            </x-filament::button>

            <div class="text-center mt-4">
                <a href="{{ filament()->getLoginUrl() }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                    Voltar para o Login
                </a>
            </div>
        </x-filament-panels::form>
    @elseif ($step === 2)
        {{-- Passo 2: QRCode (Apenas WhatsApp) --}}
        <div class="text-center space-y-6">
            <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                <h3 class="text-lg font-bold text-gray-900 mb-2">Inicie a conversa no WhatsApp</h3>
                <p class="text-sm text-gray-600 mb-4">Escaneie o QR Code abaixo ou clique no botão para solicitar seu
                    código.</p>

                <div class="flex justify-center mb-4">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=240x240&data={{ urlencode($whatsappLink) }}"
                        alt="QR Code WhatsApp" class="border rounded-lg shadow-sm" style="max-width: 200px;">
                </div>

                <x-filament::button tag="a" href="{{ $whatsappLink }}" target="_blank" color="success"
                    icon="heroicon-o-chat-bubble-left-right-ellipsis">
                    Abrir WhatsApp
                </x-filament::button>
            </div>

            <x-filament::button wire:click="goToStep3" class="w-full" color="gray">
                Já recebi o código, continuar
            </x-filament::button>
            <div class="text-center mt-2">
                <button wire:click="$set('step', 1)" class="text-sm text-gray-500 hover:underline">Voltar</button>
            </div>
        </div>
    @elseif ($step === 3)
        {{-- Passo 3: Redefinir Senha --}}
        <x-filament-panels::form wire:submit="resetPassword">
            {{ $this->form }}

            <x-filament::button type="submit" class="w-full">
                Redefinir Senha
            </x-filament::button>

            <div class="text-center mt-4">
                <button type="button" wire:click="$set('step', 1)"
                    class="text-sm font-medium text-gray-500 hover:text-gray-700">
                    Cancelar
                </button>
            </div>
        </x-filament-panels::form>
    @endif
</x-filament-panels::page.simple>