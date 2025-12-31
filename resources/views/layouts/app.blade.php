@php
    $branding = \App\Services\ResellerBranding::getCurrent();
    $appName = $branding['nome_sistema'] ?? 'Adassoft';
    $logoUrl = $branding['logo_url'] ?? asset('favicon.svg');
    $slogan = $branding['slogan'] ?? 'Tecnologia que impulsiona';
@endphp

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>@yield('title', $appName . ' | Store')</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ $logoUrl }}">

    <!-- Fonts -->
    <link href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&display=swap"
        rel="stylesheet">

    <!-- Styles (Bootstrap based but scoped or generic) -->
    <link href="{{ asset('css/sb-admin-2.min.css') }}" rel="stylesheet">

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
            color: #1cc88a !important;
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
    @yield('extra-css')
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top navbar-landing">
        <div class="container">
            <a class="navbar-brand font-weight-bold" href="{{ url('/') }}">
                <img src="{{ $logoUrl }}" width="32" height="32" class="mr-2 rounded object-contain" alt="Logo"
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
                    @if(!\App\Services\ResellerBranding::getCurrentCnpj())
                        <li class="nav-item mx-3">
                            <a class="nav-link btn-partner" href="{{ route('reseller.lp') }}">
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

    <!-- Footer -->
    <footer class="footer mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center text-white">
                    <img src="{{ $logoUrl }}" width="48" height="48" class="mb-3 rounded bg-white p-1"
                        style="object-fit: contain;"
                        onerror="this.onerror=null; this.src='{{ asset('favicon.svg') }}';">
                    <h5 class="font-weight-bold mb-3 d-block">
                        {{ $appName }}
                    </h5>
                    <p class="text-secondary small max-w-md mx-auto">{{ $slogan }}</p>
                </div>
            </div>
            <div class="text-center mt-5 border-top pt-4 border-secondary">
                <p class="mb-0 text-secondary small">&copy; {{ date('Y') }}
                    {{ $appName }}. Todos os direitos reservados.
                </p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof AOS !== 'undefined') { AOS.init({ once: true, duration: 800 }); }
        });
    </script>
    @yield('extra-js')
    @include('partials.chatwoot')
</body>

</html>