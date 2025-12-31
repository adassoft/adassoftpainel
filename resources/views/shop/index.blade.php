@extends('layouts.app')

@section('title', $branding['nome_sistema'] . ' - Softwares de Gestão')

@section('extra-css')
    <style>
        .hero-store {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            padding: 120px 0 80px;
            /* Increased top padding to account for overlap */
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
            margin-top: -75px;
            /* Compensa o padding-top do main no layout */
        }

        .hero-store::after {
            content: '';
            position: absolute;
            bottom: -50px;
            left: 0;
            width: 100%;
            height: 100px;
            background: #f8f9fc;
            transform: skewY(-2deg);
        }

        .product-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            background: #fff;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .product-img-wrapper {
            height: 220px;
            width: 100%;
            background: #eaecf4;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .product-img-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: 0.3s;
        }

        .product-card:hover .product-img-overlay {
            opacity: 1;
        }

        .category-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: rgba(255, 255, 255, 0.9);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            color: #4e73df;
            z-index: 2;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .btn-buy {
            background: #4e73df;
            color: white;
            border-radius: 10px;
            font-weight: 700;
            padding: 10px 20px;
            transition: all 0.3s;
            border: none;
            width: 100%;
        }

        .btn-buy:hover {
            background: #224abe;
            transform: scale(1.02);
            color: white;
        }

        .footer {
            background: #1a252f;
            color: #fff;
            padding: 80px 0 60px;
            text-align: center;
            border-top: 5px solid #4e73df;
        }

        .price-tag {
            font-size: 1.5rem;
            font-weight: 800;
            color: #1cc88a;
        }

        .hero-shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .shape-1 {
            width: 120px;
            height: 120px;
            top: 15%;
            left: 10%;
        }

        .shape-2 {
            width: 200px;
            height: 200px;
            top: 50%;
            right: 5%;
        }

        .shape-3 {
            width: 70px;
            height: 70px;
            bottom: 20%;
            left: 30%;
        }
    </style>
@endsection

@section('content')
    <section class="hero-store">
        <div class="hero-shape shape-1"></div>
        <div class="hero-shape shape-2"></div>
        <div class="container text-center py-5">
            <div class="mx-auto" style="max-width: 800px;">
                <h1 class="display-3 font-weight-bold mb-4" data-aos="fade-up">Nossa Loja de Softwares</h1>
                <p class="lead mb-5 opacity-75" data-aos="fade-up" data-aos-delay="100">
                    Soluções inteligentes para automatizar e escalar o seu negócio.
                </p>
                <div class="d-flex justify-content-center gap-3" data-aos="fade-up" data-aos-delay="200">
                    <a href="#produtos" class="btn btn-light btn-lg rounded-pill px-5 shadow-lg font-weight-bold">
                        Explorar Produtos
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="container mb-5" id="produtos">
        <!-- Categories Filter -->
        <div class="row mb-5" data-aos="fade-up">
            <div class="col-12">
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <button class="btn btn-outline-primary rounded-pill px-4 active">Todos</button>
                    @foreach($categorias as $cat)
                        <button class="btn btn-outline-primary rounded-pill px-4">{{ $cat }}</button>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="row">
            @foreach($produtos as $prod)
                <div class="col-xl-3 col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
                    <div class="product-card card">
                        <div class="product-img-wrapper">
                            <div class="category-badge">{{ $prod->categoria }}</div>

                            @php
                                $imgPath = $prod->imagem_destaque ?: $prod->imagem;
                                $displayPath = $imgPath ? (filter_var($imgPath, FILTER_VALIDATE_URL) ? $imgPath : asset($imgPath)) : asset('img/placeholder_card.svg');
                            @endphp

                            <img src="{{ $displayPath }}"
                                class="card-img-top {{ !$prod->imagem_destaque && $prod->imagem ? 'p-4' : '' }}"
                                style="width: 100%; height: 220px; object-fit: {{ !$prod->imagem_destaque && $prod->imagem ? 'contain' : 'cover' }};">

                            <!-- Overlay Hover -->
                            <div class="product-img-overlay">
                                <a href="{{ route('product.show', $prod->id) }}"
                                    class="btn btn-light rounded-pill px-4 font-weight-bold shadow-sm">
                                    <i class="fas fa-eye mr-2"></i> Ver Detalhes
                                </a>
                            </div>
                        </div>
                        <div class="card-body d-flex flex-column p-4">
                            <h5 class="card-title font-weight-bold text-gray-900 mb-2">{{ $prod->nome_software }}</h5>
                            <p class="card-text text-gray-600 small mb-4 flex-grow-1">
                                {{ Str::limit($prod->descricao, 100) }}
                            </p>
                            @php
                                $contactInfo = \App\Services\ResellerBranding::getContactInfo();
                            @endphp

                            <div class="mt-auto">
                                @if($contactInfo['has_payment'])
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="price-container">
                                            <span class="text-gray-500 x-small d-block">A partir de</span>
                                            <span class="price-tag">R$
                                                {{ number_format($prod->min_price ?: 0, 2, ',', '.') }}</span>
                                        </div>
                                        <div class="btn-group-buy">
                                            <a href="{{ route('product.show', $prod->id) }}#planos"
                                                class="btn btn-buy shadow-sm text-decoration-none text-center">
                                                Comprar
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <div class="text-center w-100">
                                        <div class="mb-2">
                                            <span class="badge badge-light text-muted border px-3 py-1"
                                                style="font-size: 0.9rem; font-weight: 600;">Sob Consulta</span>
                                        </div>

                                        @if(!empty($contactInfo['whatsapp']))
                                            <a href="https://wa.me/55{{ $contactInfo['whatsapp'] }}?text={{ urlencode('Olá, tenho interesse no software ' . $prod->nome_software) }}"
                                                target="_blank"
                                                class="btn btn-success btn-block shadow-sm font-weight-bold rounded-pill"
                                                style="background-color: #25D366; border-color: #25D366; color: white;">
                                                <i class="fab fa-whatsapp mr-2"></i> Falar no WhatsApp
                                            </a>
                                        @else
                                            <button class="btn btn-secondary btn-block rounded-pill" disabled>Indisponível</button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection