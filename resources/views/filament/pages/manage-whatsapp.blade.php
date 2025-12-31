<x-filament-panels::page>
    <x-filament-panels::form wire:submit="save">
        <div class="whatsapp-page-container">
            {{ $this->form }}
        </div>

        <div class="flex justify-end gap-3 mt-4">
            <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="save">
                Salvar Configurações
            </x-filament::button>
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>