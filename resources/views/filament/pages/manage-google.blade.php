<x-filament-panels::page>
    <form wire:submit.prevent="save">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" size="xl" class="px-10">
                <x-filament::loading-indicator wire:loading wire:target="save" class="h-5 w-5 mr-2" />
                Salvar Todas as Configurações
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>