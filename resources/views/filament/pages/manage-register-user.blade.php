<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit" color="success" class="w-full">
                Salvar Cadastro
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>