<div>
    <!-- Cabeçalho (Adicionado na View) -->
    <div class="text-center mb-8">
        <h2 class="text-3xl font-bold tracking-tight text-primary-600">Junte-se a nós</h2>
        <p class="mt-2 text-sm text-gray-600">
            Preencha os dados abaixo para criar sua conta gratuita.
        </p>
    </div>

    <x-filament-panels::form wire:submit="register">
        {{ $this->form }}

        <!-- Botão de Cadastro -->
        <div class="mt-6">
            <x-filament::button type="submit" form="register" class="w-full text-lg font-bold py-3" color="primary">
                CRIAR MINHA CONTA
            </x-filament::button>
        </div>
    </x-filament-panels::form>

    <!-- Links Auxiliares -->
    <div class="mt-6 text-center text-sm">
        <p class="text-gray-500">
            Já possui cadastro?
            <a href="{{ filament()->getLoginUrl() }}" class="text-primary-600 hover:text-primary-500 font-bold">
                Fazer Login
            </a>
        </p>
    </div>

    <style>
        .fi-form-actions {
            display: none !important;
        }
    </style>
</div>