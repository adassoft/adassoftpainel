@php
    $branding = \App\Services\ResellerBranding::getCurrent();
    $appName = $branding['nome_sistema'] ?? 'Adassoft';
    $logoUrl = $branding['logo_url'] ?? asset('favicon.svg');
    $iconeUrl = $branding['icone_url'] ?? asset('favicon.svg'); // Novo
    $slogan = $branding['slogan'] ?? 'Tecnologia que impulsiona';
@endphp

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Security-Policy" content="
        default-src 'self'; 
        script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.googletagmanager.com https://www.google-analytics.com https://connect.facebook.net https://www.facebook.com https://www.clarity.ms https://c.bing.com https://app.chatwoot.com; 
        style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; 
        img-src 'self' data: https://www.facebook.com https://googleads.g.doubleclick.net https://stats.g.doubleclick.net https://www.google-analytics.com; 
        font-src 'self' https://fonts.gstatic.com data:; 
        frame-src 'self' https://www.youtube.com https://www.facebook.com https://app.chatwoot.com; 
        connect-src 'self' https://www.google-analytics.com https://stats.g.doubleclick.net https://www.clarity.ms https://c.bing.com https://www.facebook.com https://app.chatwoot.com;
    ">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="@yield('meta_description', 'Softwares de Gestão simples e eficientes. Emita carnês, controle vendas e automatize processos com a AdasSoft.')">

    {{-- SEO Canonical: Evita conteúdo duplicado em revendas --}}
    @php
        $currentUrl = url()->current();
        // Pega a URL base do sistema principal (ex: https://adassoft.com.br)
        $baseUrl = config('app.url');

        // Verifica se é o domínio principal
        $isMainDomain = \App\Services\ResellerBranding::isDefault();

        // Se estiver numa revenda, forçamos o canonical para o domínio principal (para produtos/paginas padrão)
        // A menos que a view defina um canonical específico (ex: conteudo exclusivo da revenda)
        $canonical = $isMainDomain ? $currentUrl : str_replace(request()->root(), $baseUrl, $currentUrl);
    @endphp

    <link rel="canonical" href="@yield('canonical', $canonical)" />

    <title>@yield('title', $appName . ' | Store')</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ $iconeUrl }}">

    <!-- Fonts (Optimized) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- FontAwesome (Async Load) -->
    <link href="/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css" media="print" onload="this.media='all'">
    <noscript><link href="/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css"></noscript>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&display=swap" rel="stylesheet">

    <!-- Styles (Preload Critical) -->
    <link rel="preload" href="/css/sb-admin-2.min.css" as="style">
    <link href="/css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', sans-serif;
        }

        .navbar-landing {
            background-color: #ffffff !important;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1) !important;
            border-bottom: 2px solid #4e73df !important;
            padding: 10px 0;
            z-index: 9999 !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        .navbar-brand {
            font-size: 1.5rem;
            color: #4e73df !important;
            display: flex;
            align-items: center;
            font-weight: 800;
        }

        /* Ajuste para logo não distorcer */
        .navbar-brand img {
            max-height: 40px;
            width: auto;
            object-fit: contain;
        }

        .nav-link {
            color: #4e73df !important;
            font-weight: 600;
            font-size: 0.95rem;
            transition: color 0.3s;
        }

        .nav-link.active-link {
            color: #2e59d9 !important;
            font-weight: 800;
        }

        .nav-link.btn-partner {
            color: #158c61 !important;
            font-weight: 700;
        }

        .btn-login {
            border: 1px solid #4e73df !important;
            color: #4e73df !important;
            border-radius: 50px;
            padding: 6px 25px !important;
            font-weight: 600;
            margin-right: 12px;
            background: transparent;
            text-decoration: none !important;
        }

        .btn-register {
            background: #4e73df !important;
            color: white !important;
            border-radius: 50px;
            padding: 8px 25px !important;
            font-weight: 700;
            text-decoration: none !important;
            box-shadow: 0 4px 12px rgba(78, 115, 223, 0.2);
        }

        main {
            padding-top: 80px;
            min-height: 80vh;
        }

        .footer {
            background: #1a202c;
            color: white;
            padding: 60px 0 30px;
        }

        .container {
            width: 100%;
            margin-right: auto !important;
            margin-left: auto !important;
            max-width: 1240px !important;
        }
    </style>
    <style>
        /* White Label - Cores Dinâmicas */
        :root {
            --primary-gradient-start:
                {{ $branding['cor_start'] ?? '#4e73df' }}
            ;
            --primary-gradient-end:
                {{ $branding['cor_end'] ?? '#224abe' }}
            ;
            --color-accent:
                {{ $branding['cor_accent'] ?? ($branding['cor_start'] ?? '#4e73df') }}
            ;
            --color-secondary:
                {{ $branding['cor_secondary'] ?? '#858796' }}
            ;

            /* Derivados */
            --primary-btn-bg: var(--color-accent);
            /* Agora o botão principal segue o acento. */
            --primary-btn-hover: var(--primary-gradient-end);
        }

        /* Aplicações Globais que usam a cor da marca */
        .navbar-landing {
            border-bottom-color: var(--primary-gradient-start) !important;
        }

        .navbar-brand,
        .nav-link {
            color: var(--primary-gradient-start) !important;
        }

        .nav-link.active-link {
            color: var(--primary-gradient-end) !important;
        }

        .btn-login {
            border-color: var(--primary-gradient-start) !important;
            color: var(--primary-gradient-start) !important;
        }

        .btn-register {
            background: var(--primary-gradient-start) !important;
        }

        .footer {
            border-top-color: var(--primary-gradient-start) !important;
        }

        /* Bootstrap Overrides for White Label */
        .btn-primary {
            background-color: var(--primary-gradient-start) !important;
            border-color: var(--primary-gradient-start) !important;
        }

        .btn-primary:hover {
            background-color: var(--primary-gradient-end) !important;
            border-color: var(--primary-gradient-end) !important;
        }

        .btn-outline-primary {
            color: var(--primary-gradient-start) !important;
            border-color: var(--primary-gradient-start) !important;
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-gradient-start) !important;
            color: #fff !important;
        }

        .text-primary {
            color: var(--primary-gradient-start) !important;
        }

        .border-primary {
            border-color: var(--primary-gradient-start) !important;
        }

        /* Novas Classes Utilitárias */
        .text-accent {
            color: var(--color-accent) !important;
        }

        .bg-accent {
            background-color: var(--color-accent) !important;
            color: white;
        }

        .border-accent {
            border-color: var(--color-accent) !important;
        }

        .text-secondary-custom {
            color: var(--color-secondary) !important;
        }

        .bg-secondary-custom {
            background-color: var(--color-secondary) !important;
        }
    </style>
    @if(!empty($branding['google_analytics_id']))
        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $branding['google_analytics_id'] }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag() { dataLayer.push(arguments); }
            gtag('js', new Date());

            gtag('config', '{{ $branding['google_analytics_id'] }}');
        </script>
    @endif

    @if(!empty($branding['facebook_pixel_id']))
        <!-- Meta Pixel Code -->
        <script>
            !function (f, b, e, v, n, t, s) {
                if (f.fbq) return; n = f.fbq = function () {
                    n.callMethod ?
                    n.callMethod.apply(n, arguments) : n.queue.push(arguments)
                };
                if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0';
                n.queue = []; t = b.createElement(e); t.async = !0;
                t.src = v; s = b.getElementsByTagName(e)[0];
                s.parentNode.insertBefore(t, s)
            }(window, document, 'script',
                'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', '{{ $branding['facebook_pixel_id'] }}');
            fbq('track', 'PageView');
        </script>
        <noscript><img height="1" width="1" style="display:none"
                src="https://www.facebook.com/tr?id={{ $branding['facebook_pixel_id'] }}&ev=PageView&noscript=1" /></noscript>
        <!-- End Meta Pixel Code -->
    @endif

    @if(!empty($branding['microsoft_clarity_id']))
        <!-- Microsoft Clarity -->
        <script type="text/javascript">
            (function(c,l,a,r,i,t,y){
                c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
                t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
                y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
            })(window, document, "clarity", "script", "{{ $branding['microsoft_clarity_id'] }}");
        </script>
    @endif

    @yield('extra-css')
</head>


<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top navbar-landing">
        <div class="container">
            <a class="navbar-brand font-weight-bold" href="{{ url('/') }}">
                <img src="{{ $logoUrl }}" class="mr-2" alt="Logo" width="150" height="40"
                    onerror="this.onerror=null; this.src='{{ asset('favicon.svg') }}';">
                <span>{{ $appName }}</span>
            </a>
            <button class="navbar-toggler border-0" type="button" data-toggle="collapse" data-target="#navbarNav">
                <i class="fas fa-bars text-primary"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto align-items-center">
                    <li class="nav-item mx-3">
                        <a class="nav-link {{ Request::is('/') ? 'active-link' : '' }}" href="{{ url('/') }}">Home</a>
                    </li>
                    <li class="nav-item mx-3">
                        <a class="nav-link" href="{{ url('/') }}#produtos">Catálogo</a>
                    </li>
                    <li class="nav-item mx-3">
                        <a class="nav-link {{ Request::routeIs('downloads') ? 'active-link' : '' }}"
                            href="{{ route('downloads') }}">Downloads</a>
                    </li>
                    <li class="nav-item mx-3">
                        <a class="nav-link {{ Request::routeIs('kb.*') ? 'active-link' : '' }}"
                            href="{{ route('kb.index') }}">Ajuda</a>
                    </li>
                    @if(\App\Services\ResellerBranding::isDefault())
                        <li class="nav-item mx-3">
                            <a class="nav-link btn-partner" href="{{ route('partners.index') }}">
                                <i class="fas fa-handshake mr-1"></i> Seja Parceiro
                            </a>
                        </li>
                    @endif
                    @guest
                        <li class="nav-item ml-lg-4">
                            <a href="{{ url('/app/login') }}" class="btn-login">
                                Área do Cliente
                            </a>
                        </li>
                    @endguest
                    @auth
                        <li class="nav-item ml-lg-4">
                            <a href="{{ url('/admin') }}" class="btn-login">
                                <i class="fas fa-user-circle mr-1"></i> Painel
                            </a>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    @php
        $config = \App\Services\ResellerBranding::getConfig();
        $user = $config?->user;
        $empresa = $user?->empresa;

        // Dados padrão (Fallback final)
        $defaultCnpj = '28.718.938/0001-60';
        $defaultEmail = 'suporte@adassoft.com';
        $defaultWhatsapp = '11999999999';

        // Redes Sociais (Exclusivo da Config)
        $instagram = $config->instagram_url ?? null;
        $facebook = $config->facebook_url ?? null;
        $linkedin = $config->linkedin_url ?? null;
        $youtube = $config->youtube_url ?? null;

        // Contatos: Config > Empresa > User > Padrão
        $email = $config->email_suporte
            ?? $empresa->email
            ?? $user->email
            ?? $defaultEmail;

        // Whatsapp/Fone: Config > Empresa > Padrão
        $rawPhone = $config->whatsapp
            ?? $empresa->fone
            ?? $defaultWhatsapp;
        $whatsapp = preg_replace('/[^0-9]/', '', $rawPhone);

        // Endereço e Horário (Config > Empresa > Padrão)
        $endereco = $config->endereco
            ?? ($empresa ? "{$empresa->endereco}, {$empresa->numero} - {$empresa->cidade}/{$empresa->uf}" : null)
            ?? 'Av. Paulista, 1000 - Bela Vista - SP';

        $horario = $config->horario_atendimento ?? 'Seg à Sex, 09h às 18h';

        $exibirDoc = $config->exibir_documento ?? true;

        // CNPJ
        $cnpj = $empresa->cnpj ?? $user->cnpj ?? $defaultCnpj;

        // Helper formatação simples
        $formatPhone = function ($phone) {
            $phone = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($phone) > 10)
                return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 5) . '-' . substr($phone, 7);
            if (strlen($phone) == 10)
                return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 4) . '-' . substr($phone, 6);
            return $phone;
        };
    @endphp

    <!-- Footer -->
    <footer class="footer mt-auto" style="background-color: #1a202c; padding-top: 60px; padding-bottom: 30px;">
        <div class="container">
            <div class="row text-white text-md-left text-center">
                <!-- Coluna 1: Empresa -->
                <div class="col-md-4 mb-4 text-center text-md-left">
                    <img src="{{ $iconeUrl }}" width="48" height="48" alt="{{ $appName ?? 'Logo' }}"
                        class="mb-3 rounded bg-white p-1 mx-auto mx-md-0 d-block" style="object-fit: contain;"
                        onerror="this.onerror=null; this.src='{{ asset('favicon.svg') }}';">
                    <h5 class="font-weight-bold mb-2">{{ $appName }}</h5>
                    <p class="text-white-50 small mt-3">
                        @if($exibirDoc) <strong>CNPJ:</strong> {{ $cnpj }}<br> @endif
                        {{ $endereco }}<br>
                        {{ $horario }}
                    </p>
                </div>

                <!-- Coluna 2: Institucional -->
                <div class="col-md-2 mb-4 text-center text-md-left">
                    <h6 class="font-weight-bold text-uppercase mb-3 small text-white" style="letter-spacing: 1px;">
                        Navegação</h6>
                    <ul class="list-unstyled text-white-50 small">
                        <li class="mb-2"><a href="{{ url('/') }}"
                                class="text-reset text-decoration-none hover-white">Início</a></li>
                        <li class="mb-2"><a href="{{ route('downloads') }}"
                                class="text-reset text-decoration-none hover-white">Downloads</a></li>
                        <li class="mb-2"><a href="{{ route('kb.index') }}"
                                class="text-reset text-decoration-none hover-white">Central de Ajuda</a></li>
                        <li class="mb-2"><a href="{{ route('partners.index') }}"
                                class="text-reset text-decoration-none hover-white">Seja Parceiro</a></li>
                        <li class="mb-2"><a href="{{ url('/login') }}"
                                class="text-reset text-decoration-none hover-white">Login</a></li>
                    </ul>
                </div>

                <!-- Coluna 3: Legal -->
                <div class="col-md-3 mb-4 text-center text-md-left">
                    <h6 class="font-weight-bold text-uppercase mb-3 small text-white" style="letter-spacing: 1px;">Legal
                        & Políticas</h6>
                    <ul class="list-unstyled text-white-50 small">
                        @php
                            try {
                                $legalPages = \App\Models\LegalPage::where('is_active', true)->get();
                            } catch (\Exception $e) {
                                $legalPages = [];
                            }
                        @endphp

                        @foreach($legalPages as $page)
                            <li class="mb-2">
                                <a href="{{ route('legal.show', $page->slug) }}"
                                    class="text-reset text-decoration-none hover-white">
                                    {{ $page->title }}
                                </a>
                            </li>
                        @endforeach

                        @if(empty($legalPages) || $legalPages->isEmpty())
                            <li class="mb-2 text-white-50 font-italic">Sem documentos públicos.</li>
                        @endif
                    </ul>
                </div>

                <!-- Coluna 4: Contato -->
                <div class="col-md-3 mb-4 text-center text-md-left">
                    <h6 class="font-weight-bold text-uppercase mb-3 small text-white" style="letter-spacing: 1px;">
                        Atendimento</h6>
                    <ul class="list-unstyled text-white-50 small">
                        <li class="mb-2"><i class="fas fa-envelope mr-2"></i> {{ $email }}</li>
                        <li class="mb-2"><i class="fab fa-whatsapp mr-2"></i> {{ $formatPhone($whatsapp) }}</li>
                    </ul>
                    <div class="mt-3">
                        @if($instagram) <a href="{{ $instagram }}" target="_blank"
                        class="text-white mr-3 hover-opacity"><i class="fab fa-instagram fa-lg"></i></a> @endif
                        @if($facebook) <a href="{{ $facebook }}" target="_blank"
                        class="text-white mr-3 hover-opacity"><i class="fab fa-facebook fa-lg"></i></a> @endif
                        @if($linkedin) <a href="{{ $linkedin }}" target="_blank"
                        class="text-white mr-3 hover-opacity"><i class="fab fa-linkedin fa-lg"></i></a> @endif
                        @if($youtube) <a href="{{ $youtube }}" target="_blank" class="text-white hover-opacity"><i
                        class="fab fa-youtube fa-lg"></i></a> @endif
                    </div>
                </div>
            </div>

            <div class="row mt-4 pt-4 border-top border-secondary">
                <div class="col-12 text-center text-white-50 small">
                    &copy; {{ date('Y') }} {{ $appName }}. Todos os direitos reservados.
                    <br class="d-md-none"> @if($exibirDoc) CNPJ: {{ $cnpj }} @endif
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="/vendor/jquery/jquery.min.js"></script>
    <script src="/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof AOS !== 'undefined') { AOS.init({ once: true, duration: 800 }); }
        });
    </script>
    @yield('extra-js')
    @include('partials.chatwoot')
</body>

</html>