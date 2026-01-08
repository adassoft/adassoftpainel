@php
    // Lógica de Branding diretamente no Layout para garantir que carregue sempre
    $branding = \App\Services\ResellerBranding::getCurrent();
    $gradientStart = $branding['cor_start'] ?? '#1a2980';
    $gradientEnd = $branding['cor_end'] ?? '#26d0ce';
    $appName = $branding['nome_sistema'] ?? config('app.name', 'Adassoft');
    $slogan = $branding['slogan'] ?? 'Segurança e Gestão de Licenças';
    $logoUrl = $branding['logo_url'] ?? asset('favicon.svg');
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    <div class="min-h-screen w-full flex bg-gray-50 overflow-x-hidden">

        <!-- Lado Esquerdo: Branding (Desktop Only) -->
        <div class="hidden lg:flex w-1/2 relative flex-col items-center p-12 text-center text-white overflow-hidden"
            style="background: linear-gradient(135deg, {{ $gradientStart }}, {{ $gradientEnd }});">

            <!-- Efeitos de Fundo removidos para evitar bugs visuais (Bola Branca) -->
            {{-- <div class="absolute -top-12 -left-12 w-48 h-48 bg-white opacity-10 rounded-full blur-2xl"></div>
            <div class="absolute -bottom-12 -right-12 w-64 h-64 bg-white opacity-5 rounded-full blur-3xl"></div> --}}

            <!-- Conteúdo Principal: flex-grow faz ocupar todo espaço disponível, empurrando o footer para baixo e centralizando o conteúdo -->
            <div class="flex-grow flex flex-col justify-center items-center relative z-10 w-full animate-fade-in-up">
                <img src="{{ $logoUrl }}"
                    class="h-32 mx-auto mb-8 drop-shadow-2xl hover:scale-105 transition-transform duration-300"
                    onerror="this.onerror=null; this.src='{{ asset('favicon.svg') }}';">
                <h1 class="text-5xl font-extrabold mb-4 tracking-tight drop-shadow-sm font-heading">{{ $appName }}</h1>
                <p class="text-xl opacity-90 font-light max-w-lg mx-auto leading-relaxed">{{ $slogan }}</p>
            </div>

            <!-- Footerzinho da Marca -->
            <div class="relative z-10 text-xs opacity-60 mt-auto">
                &copy; {{ date('Y') }} {{ $appName }}. Todos os direitos reservados.
            </div>
        </div>

        <!-- Lado Direito: Formulário (Agora largo e confortável) -->
        <div
            class="w-full lg:w-1/2 flex flex-col justify-center items-center px-4 py-8 sm:p-12 lg:p-24 bg-white relative">
            <div class="w-full max-w-2xl space-y-8">

                <!-- Logo Mobile -->
                <div class="lg:hidden text-center mb-8">
                    <img src="{{ $logoUrl }}" class="h-16 mx-auto mb-4"
                        onerror="this.onerror=null; this.src='{{ asset('favicon.svg') }}';">
                    <h2 class="text-2xl font-bold text-gray-900">{{ $appName }}</h2>
                </div>

                <!-- O Formulário do Filament será injetado aqui -->
                <div class="mt-10">
                    {{ $slot }}
                </div>

            </div>
        </div>
    </div>

    <!-- Estilos Extras para garantir beleza e Scripts White Label -->
    <style>
        body {
            margin: 0;
            padding: 0;
        }

        .font-heading {
            font-family: 'Nunito', sans-serif;
        }
    </style>

    <script>
        // White Label: Atualiza o Título da Aba com o Nome da Revenda
        // Pega o título atual da página (definido pelo Filament) e prefixa com o nome da revenda
        var pageTitle = "{{ $appName }}";
        if (document.title && !document.title.includes(pageTitle)) {
            document.title = pageTitle + ' - ' + document.title;
        }

        // White Label: Atualiza Favicon se houver logo customizado
        @if(isset($branding['logo_url']))
            var link = document.querySelector("link[rel~='icon']");
            if (!link) {
                link = document.createElement('link');
                link.rel = 'icon';
                document.getElementsByTagName('head')[0].appendChild(link);
            }
            link.href = "{{ $branding['logo_url'] }}";
        @endif
    </script>
</x-filament-panels::layout.base>