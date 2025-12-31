<div>
    <x-filament-panels::form wire:submit="register">
        {{ $this->form }}

        <!-- Botão de Cadastro -->
        <div class="mt-6">
            <x-filament::button type="submit" form="register" class="w-full">
                {{ __('filament-panels::pages/auth/register.form.actions.register.label') }}
            </x-filament::button>
        </div>
    </x-filament-panels::form>

    <!-- Links Auxiliares -->
    <div class="mt-6 text-center text-sm">
        <p class="text-gray-500">
            {{ __('filament-panels::pages/auth/register.actions.login.before') }}
            <a href="{{ filament()->getLoginUrl() }}" class="text-primary-600 hover:text-primary-500 font-bold">
                {{ __('filament-panels::pages/auth/register.actions.login.label') }}
            </a>
        </p>
    </div>

    <style>
        .fi-form-actions {
            display: none !important;
        }
    </style>
</div>