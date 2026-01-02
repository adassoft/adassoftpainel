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
            color: #4e73df;
            font-size: 2rem;
            border: 1px solid #eaecf4;
        }

        .hero-downloads {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
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
            background: #4e73df;
            color: white !important;
            border-radius: 50px;
            padding: 10px 20px;
            font-weight: 700;
            transition: all 0.3s;
        }

        .btn-download:hover {
            background: #2e59d9;
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(78, 115, 223, 0.3);
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

    <!-- Lista de Downloads -->
    <div class="container mb-5 pb-5">
        @if(count($downloads) == 0)
            <div class="text-center py-5" data-aos="fade-up">
                <div class="mb-4">
                    <i class="fas fa-box-open fa-4x text-gray-300"></i>
                </div>
                <h4 class="text-gray-600 font-weight-bold">Nenhum download disponível no momento.</h4>
                <p class="text-muted">Estamos organizando nossos arquivos. Volte mais tarde ou entre em contato com nosso
                    suporte.</p>
                <a href="{{ route('home') }}" class="btn btn-primary rounded-pill px-4 mt-3">Ir para a Loja</a>
            </div>
        @else
            <div class="row">
                @foreach ($downloads as $soft)
                    <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="{{ $loop->index * 50 }}">
                        <div class="card download-card h-100">
                            <div class="card-body text-center p-5 d-flex flex-column">
                                <div class="icon-box">
                                    @if(!empty($soft['imagem']))
                                        <img src="{{ $soft['imagem'] }}" class="img-fluid rounded-circle"
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
                                        <div class="mt-2 text-primary font-weight-bold">
                                            <i class="fas fa-cloud-download-alt mr-1"></i> {{ $soft['contador'] }} downloads realizados
                                        </div>
                                    @endif
                                </div>

                                <a href="{{ $soft['url_download'] }}" target="_blank" class="btn btn-download btn-block shadow-sm">
                                    <i class="fas fa-download mr-2"></i> Baixar Arquivo
                                </a>

                                <div class="mt-3">
                                    @if($soft['tipo'] == 'software')
                                        <a href="{{ route('product.show', $soft['slug'] ?? $soft['id']) }}"
                                            class="small text-primary font-weight-bold text-decoration-none hover:underline">
                                            <i class="fas fa-info-circle mr-1"></i> Detalhes do Produto
                                        </a>
                                    @else
                                        <a href="{{ route('download.show', $soft['slug'] ?? $soft['id']) }}"
                                            class="small text-secondary font-weight-bold text-decoration-none hover:underline">
                                            <i class="fas fa-info-circle mr-1"></i> Detalhes do Download
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection