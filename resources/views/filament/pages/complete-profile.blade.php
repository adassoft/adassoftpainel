<x-filament-panels::page>
    <div class="max-w-4xl mx-auto">
        <form wire:submit="save">
            {{ $this->form }}

            <div class="mt-4 flex justify-end">
                <x-filament::button type="submit" size="lg">
                    Confirmar e Acessar Painel
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>