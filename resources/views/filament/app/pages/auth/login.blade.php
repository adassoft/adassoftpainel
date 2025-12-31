<div>
    <x-filament-panels::form wire:submit="authenticate">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" form="authenticate" class="w-full">
                {{ __('filament-panels::pages/auth/login.form.actions.authenticate.label') }}
            </x-filament::button>
        </div>
    </x-filament-panels::form>

    <!-- Links Auxiliares -->
    <div class="mt-6 text-center text-sm">
        @if (filament()->hasPasswordReset())
            <a href="{{ filament()->getRequestPasswordResetUrl() }}"
                class="text-primary-600 hover:text-primary-500 font-semibold block mb-2">
                {{ __('filament-panels::pages/auth/login.actions.request_password_reset.label') }}
            </a>
        @endif

        @if (filament()->hasRegistration())
            <p class="text-gray-500">
                Ainda não tem conta?
                <a href="{{ filament()->getRegistrationUrl() }}" class="text-primary-600 hover:text-primary-500 font-bold">
                    {{ __('filament-panels::pages/auth/login.actions.register.label') }}
                </a>
            </p>
        @endif
    </div>

    <style>
        /* Esconder o botão padrão do form se ele aparecer duplicado */
        .fi-form-actions {
            display: none !important;
        }
    </style>
</div>