<x-filament-panels::page>
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}

        <div class="flex justify-end gap-x-3 mt-4">
            <x-filament::button type="submit" color="primary">
                Salvar Dados da Empresa
            </x-filament::button>
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>