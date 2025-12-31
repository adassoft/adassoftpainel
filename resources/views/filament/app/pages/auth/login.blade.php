@php
    // Configuração Padrão (Adassoft)
    $gradientStart = '#1a2980';
    $gradientEnd = '#26d0ce';
    $appName = config('app.name', 'Adassoft');
    $slogan = 'Segurança e Gestão de Licenças';
    $logoUrl = asset('favicon.svg');

    // Tenta carregar Branding da Revenda via Domínio
    $branding = \App\Services\ResellerBranding::getCurrent();

    if ($branding) {
        $gradientStart = $branding['gradient_start'] ?? $gradientStart;
        $gradientEnd = $branding['gradient_end'] ?? $gradientEnd;
        $appName = $branding['nome_sistema'] ?? $appName;
        $slogan = $branding['slogan'] ?? $slogan;
        $logoUrl = $branding['logo_url'] ?? $logoUrl;
    }
@endphp

<x-filament-panels::layout.base :livewire="$this">
    <div class="min-h-screen flex items-center justify-center bg-gray-100 p-4 sm:p-0">

        <div
            class="w-full max-w-7xl bg-white shadow-2xl rounded-2xl overflow-hidden flex flex-col md:flex-row min-h-[600px]">

            <!-- Lado Esquerdo: Branding (Gradient) -->
            <div class="w-full md:w-1/2 relative flex flex-col items-center justify-center p-12 text-center text-white overflow-hidden"
                style="background: linear-gradient(135deg, {{ $gradientStart }}, {{ $gradientEnd }});">

                <!-- Efeitos de Fundo (Círculos) -->
                <div class="absolute -top-12 -left-12 w-48 h-48 bg-white opacity-10 rounded-full blur-2xl"></div>
                <div class="absolute -bottom-12 -right-12 w-64 h-64 bg-white opacity-5 rounded-full blur-3xl"></div>

                <div class="relative z-10 animate-fade-in-up">
                    <img src="{{ $logoUrl }}"
                        class="h-24 mx-auto mb-6 drop-shadow-md hover:scale-105 transition-transform duration-300">
                    <h1 class="text-4xl font-bold mb-2 tracking-tight drop-shadow-sm">{{ $appName }}</h1>
                    <p class="text-lg opacity-90 font-light">{{ $slogan }}</p>
                </div>
            </div>

            <!-- Lado Direito: Login Form -->
            <div class="w-full md:w-1/2 bg-white flex flex-col justify-center p-6 md:p-10">
                <div class="w-full max-w-md mx-auto">
                    <div class="text-center mb-10">
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">Bem-vindo de volta!</h2>
                        <p class="text-gray-500 text-sm">Acesse seu painel administrativo</p>
                    </div>

                    <!-- Mensagens de Erro/Sucesso do Filament -->
                    {{ $this->form }}

                    <div class="mt-8 text-center space-y-4">
                        <x-filament::button type="submit" form="authenticate"
                            class="w-full py-3 text-lg font-bold shadow-lg transform transition hover:-translate-y-1 hover:shadow-xl bg-blue-600 hover:bg-blue-700">
                            Acessar Sistema
                        </x-filament::button>

                        <!-- Google Config (se existir) -->
                        <div class="relative my-6">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-200"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-2 bg-white text-gray-400">ou</span>
                            </div>
                        </div>

                        <button
                            class="w-full flex items-center justify-center gap-2 bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 font-medium py-2.5 px-4 rounded-lg transition-colors shadow-sm">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M21.35,11.1H12.18V13.83H18.69C18.36,17.64 15.19,19.27 12.19,19.27C8.36,19.27 5,16.25 5,12.61C5,8.86 8.35,5.97 12.18,5.97C14.61,5.97 16.28,7.02 17.15,7.85L19.32,5.67C17.65,4.02 15.11,2.83 12.19,2.83C6.7,2.83 2,7.31 2,12.87C2,18.42 6.7,22.91 12.19,22.91C17.48,22.91 21.66,19.23 21.66,13.67C21.66,12.55 21.57,11.85 21.35,11.1Z" />
                            </svg>
                            Continuar com Google
                        </button>
                    </div>

                    <div class="mt-8 text-center text-sm space-y-2">
                        <a href="#"
                            class="block text-blue-600 hover:text-blue-800 font-semibold hover:underline">Esqueceu a
                            senha?</a>
                        <p class="text-gray-500">
                            Ainda não tem conta? <a href="#"
                                class="text-blue-600 hover:text-blue-800 font-bold hover:underline">Cadastre-se</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Injetar Estilos especificos -->
        <style>
            .fi-form-actions {
                display: none !important;
            }
        </style>
    </div>
</x-filament-panels::layout.base>