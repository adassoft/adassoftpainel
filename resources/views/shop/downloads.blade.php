@extends('layouts.app')

@section('title', 'Central de Downloads - Adassoft')

@section('extra-css')
    <style>
        .download-card {
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            background: #fff;
        }

        .download-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .icon-box {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fc;
            border-radius: 50%;
            margin: 0 auto 20px;
            color: var(--primary-gradient-start);
            font-size: 2rem;
            border: 1px solid #eaecf4;
        }

        .hero-downloads {
            background: linear-gradient(135deg, var(--primary-gradient-start) 0%, var(--primary-gradient-end) 100%);
            color: white;
            padding: 80px 0;
            margin-bottom: 50px;
            position: relative;
            margin-top: -72px;
            /* Compensa o padding do layout */
            overflow: hidden;
        }

        .hero-downloads::after {
            content: '';
            position: absolute;
            bottom: -30px;
            left: 0;
            width: 100%;
            height: 60px;
            background: #f8f9fc;
            transform: skewY(-1.5deg);
        }

        .btn-download {
            background: var(--primary-gradient-start);
            color: white !important;
            border-radius: 50px;
            padding: 10px 20px;
            font-weight: 700;
            transition: all 0.3s;
        }

        .btn-download:hover {
            background: var(--primary-gradient-end);
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
    </style>
@endsection

@section('content')
    <!-- Header -->
    <header class="hero-downloads text-center">
        <div class="container py-5" data-aos="fade-down">
            <h1 class="font-weight-bold display-4">Central de Downloads</h1>
            <p class="lead mb-0 opacity-8">Baixe os instaladores oficiais e ferramentas essenciais para sua operação.</p>
        </div>
    </header>

    <!-- Busca e Lista de Downloads -->
    <div class="container mb-5 pb-5">

        <!-- Search Bar -->
        <div class="row justify-content-center mb-5" style="margin-top: -80px; position: relative; z-index: 10;">
            <div class="col-md-8 col-lg-6">
                <form action="{{ route('downloads') }}" method="GET" class="card shadow-lg border-0">
                    <div class="input-group input-group-lg">
                        <input type="text" name="q" class="form-control border-0 pl-4"
                            placeholder="Pesquisar software, manual ou driver..." value="{{ request('q') }}"
                            style="border-radius: 10px 0 0 10px; height: 60px;">
                        <input type="hidden" name="page" value="1">
                        <div class="input-group-append">
                            <button class="btn btn-white bg-white pr-4" type="submit"
                                style="border-radius: 0 10px 10px 0; color: var(--primary-gradient-start);">
                                <i class="fas fa-search fa-lg"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if(count($downloads) == 0)
            <div class="text-center py-5" data-aos="fade-up">
                <div class="mb-4">
                    <i class="fas fa-search fa-4x text-gray-300"></i>
                </div>
                <h4 class="text-gray-600 font-weight-bold">
                    {{ request('q') ? 'Nenhum resultado encontrado para "' . request('q') . '"' : 'Nenhum download disponível no momento.' }}
                </h4>
                <p class="text-muted">Tente outro termo ou navegue pelo menu.</p>
                <a href="{{ route('downloads') }}" class="btn btn-primary rounded-pill px-4 mt-3">Limpar Busca</a>
            </div>
        @else
            <div class="row pt-3">
                @foreach ($downloads as $soft)
                    <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="{{ $loop->index * 50 }}">
                        <div class="card download-card h-100">
                            <div class="card-body text-center p-5 d-flex flex-column">
                                <div class="icon-box">
                                    @if(!empty($soft['imagem']))
                                        @php
                                            $imgPathRaw = $soft['imagem'];
                                            $displayPath = '/img/placeholder_card.svg';

                                            if ($imgPathRaw) {
                                                if (filter_var($imgPathRaw, FILTER_VALIDATE_URL)) {
                                                    $displayPath = $imgPathRaw;
                                                } elseif (Str::startsWith($imgPathRaw, 'img/') || Str::startsWith($imgPathRaw, '/img/')) {
                                                    $displayPath = Storage::url($imgPathRaw);
                                                } else {
                                                    $displayPath = Storage::url($imgPathRaw);
                                                }
                                            }
                                        @endphp
                                        <img src="{{ $displayPath }}" class="img-fluid rounded-circle"
                                            style="width:100%; height:100%; object-fit:cover;">
                                    @else
                                        <i class="fas fa-file-download"></i>
                                    @endif
                                </div>

                                <h3 class="h5 font-weight-bold text-gray-900 mb-2">
                                    {{ $soft['nome_software'] }}
                                </h3>

                                <div class="text-muted small mb-4 flex-grow-1">
                                    <div class="mb-1">Versão: <span
                                            class="font-weight-bold text-dark">{{ $soft['versao'] ?: '-' }}</span></div>
                                    @if(!empty($soft['tamanho_arquivo']))
                                        <div class="mb-1">Tamanho: <span
                                                class="font-weight-bold text-dark">{{ $soft['tamanho_arquivo'] }}</span></div>
                                    @endif
                                    @if(!empty($soft['data_info']))
                                        <div class="mb-1">Atualizado: <span
                                                class="font-weight-bold text-dark">{{ $soft['data_info'] }}</span></div>
                                    @endif
                                    @if(isset($soft['contador']) && $soft['contador'] > 0)
                                        <div class="mt-2 text-primary font-weight-bold"
                                            style="color: var(--primary-gradient-start) !important;">
                                            <i class="fas fa-cloud-download-alt mr-1"></i> {{ $soft['contador'] }} downloads realizados
                                        </div>
                                    @endif

                                    @if(isset($soft['is_paid']) && $soft['is_paid'])
                                        <div class="position-absolute" style="top: 15px; right: 15px; z-index: 10;">
                                            <span class="badge badge-pill badge-warning shadow-sm px-3 py-2 text-dark font-weight-bold">
                                                <i class="fas fa-tag mr-1"></i> R$ {{ number_format($soft['preco'], 2, ',', '.') }}
                                            </span>
                                        </div>
                                    @endif

                                    @if(!empty($soft['os_list']))
                                        <div class="mt-3 text-center">
                                            @foreach($soft['os_list'] as $os)
                                                @php
                                                    $osIcons = [
                                                        'windows' => 'fab fa-windows text-primary',
                                                        'linux' => 'fab fa-linux text-warning',
                                                        'mac' => 'fab fa-apple text-dark',
                                                        'android' => 'fab fa-android text-success',
                                                        'ios' => 'fab fa-app-store-ios text-info',
                                                        'any' => 'fas fa-laptop text-secondary'
                                                    ];
                                                    $osIcon = $osIcons[$os] ?? $osIcons['any'];
                                                @endphp
                                                <i class="{{ $osIcon }} mx-1 fa-lg" title="{{ ucfirst($os) }}"></i>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                @php
                                    $hasMultipleOs = !empty($soft['os_list']) && count($soft['os_list']) > 1;
                                    $isPaid = $soft['is_paid'] ?? false;
                                    $requiresLogin = $soft['requires_login'] ?? false;
                                    $detailsRoute = route('downloads.show', $soft['repo_slug'] ?? $soft['repo_id'] ?? $soft['slug'] ?? $soft['id']);
                                    $downloadRoute = route('downloads.file', $soft['slug'] ?? $soft['id']);

                                    $shouldGoToDetails = $hasMultipleOs || $isPaid || $requiresLogin;
                                @endphp

                                @if($shouldGoToDetails)
                                    <a href="{{ $detailsRoute }}" class="btn btn-download btn-block shadow-sm">
                                        @if($isPaid)
                                            <i class="fas fa-shopping-cart mr-2"></i> Ver / Comprar
                                        @elseif($requiresLogin)
                                            <i class="fas fa-lock mr-2"></i> Acessar
                                        @else
                                            <i class="fas fa-list mr-2"></i> Escolher Versão
                                        @endif
                                    </a>
                                @else
                                    <a href="{{ $downloadRoute }}" target="_blank" class="btn btn-download btn-block shadow-sm">
                                        <i class="fas fa-download mr-2"></i> Baixar Arquivo
                                    </a>
                                @endif

                                <div class="mt-3">
                                    <a href="{{ route('downloads.show', $soft['repo_slug'] ?? $soft['repo_id'] ?? $soft['slug'] ?? $soft['id']) }}"
                                        class="small text-secondary font-weight-bold text-decoration-none hover:underline">
                                        <i class="fas fa-info-circle mr-1"></i> Detalhes do Arquivo
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination Links -->
            <div class="d-flex justify-content-center mt-5">
                {{ $downloads->links() }}
            </div>
        @endif
    </div>
@endsection