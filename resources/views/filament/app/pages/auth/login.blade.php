<div>
    <!-- Cabeçalho (Adicionado na View) -->
    <div class="text-center mb-8">
        <h2 class="text-3xl font-bold tracking-tight text-gray-900">Acesse sua Conta</h2>
        <p class="mt-2 text-sm text-gray-600">
            Bem-vindo de volta! Insira seus dados para entrar.
        </p>
    </div>

    <x-filament-panels::form wire:submit="authenticate">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" form="authenticate" class="w-full">
                Entrar no Sistema
            </x-filament::button>
        </div>
    </x-filament-panels::form>

    <!-- Links Auxiliares -->
    <div class="mt-6 text-center text-sm">
        @if (filament()->hasPasswordReset())
            <a href="{{ filament()->getRequestPasswordResetUrl() }}"
                class="text-primary-600 hover:text-primary-500 font-semibold block mb-2">
                Esqueceu sua senha?
            </a>
        @endif

        @if (filament()->hasRegistration())
            <p class="text-gray-500">
                Ainda não tem conta?
                <a href="{{ filament()->getRegistrationUrl() }}" class="text-primary-600 hover:text-primary-500 font-bold">
                    Cadastre-se aqui
                </a>
            </p>
        @endif
    </div>

    <style>
        .fi-form-actions {
            display: none !important;
        }
    </style>
</div>