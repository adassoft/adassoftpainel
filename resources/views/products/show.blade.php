@extends('layouts.app')

@section('title', $product->nome_software)

@section('content')

    <!-- Navbar is handled in layout -->

    <!-- Hero / Custom Landing Page -->
    @if(!empty($product->pagina_vendas_html))
        {!! $product->pagina_vendas_html !!}
    @else
        <!-- Default Header if no custom HTML -->
        <div class="bg-white py-12 mb-12 shadow-sm border-b">
            <div class="container mx-auto px-4">
                <div class="flex flex-col md:flex-row items-center gap-10">
                    <!-- Product Image -->
                    <div class="w-full md:w-1/3 flex justify-center">
                        <div class="relative group">
                            @php
                                $imgPathRaw = $product->imagem_destaque ?: $product->imagem;
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
                            <img src="{{ $displayPath }}" alt="{{ $product->nome_software }}"
                                class="rounded-2xl shadow-2xl max-h-[400px] w-auto object-contain transform group-hover:scale-105 transition-transform duration-500">
                            <div
                                class="absolute -bottom-4 -right-4 bg-blue-600 text-white p-4 rounded-xl shadow-lg hidden md:block">
                                <i class="fas fa-shield-alt text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Product Info -->
                    <div class="w-full md:w-2/3 text-center md:text-left">
                        <span
                            class="inline-block bg-blue-100 text-blue-800 text-xs px-3 py-1 rounded-full mb-4 font-bold uppercase tracking-wider">
                            {{ $product->categoria ?? 'Software' }}
                        </span>
                        <h1 class="text-4xl md:text-6xl font-extrabold text-gray-900 mb-6 leading-tight">
                            {{ $product->nome_software }} <span
                                class="text-2xl font-normal" style="color: var(--primary-gradient-start)">v{{ $product->versao ?? '1.0' }}</span>
                        </h1>
                        <p class="text-xl text-gray-600 mb-8 max-w-2xl leading-relaxed">
                            {{ $product->descricao }}
                        </p>

                        <div
                            class="flex flex-wrap justify-center md:justify-start gap-6 mb-8 text-sm font-semibold text-gray-500">
                            <div class="flex items-center bg-gray-50 px-4 py-2 rounded-lg border border-gray-100">
                                <i class="fas fa-headset text-green-500 mr-2"></i>
                                Suporte Incluso
                            </div>
                            <div class="flex items-center bg-gray-50 px-4 py-2 rounded-lg border border-gray-100">
                                <i class="fas fa-sync text-green-500 mr-2"></i>
                                Atualizações Grátis
                            </div>
                            <div class="flex items-center bg-gray-50 px-4 py-2 rounded-lg border border-gray-100">
                                <i class="fas fa-lock text-green-500 mr-2"></i>
                                Garantia de 7 dias
                            </div>
                        </div>

                        <a href="#planos"
                            style="background-color: var(--color-accent);"
                            class="inline-flex items-center justify-center px-8 py-4 text-white font-bold rounded-full hover:opacity-90 transition shadow-lg hover:shadow-xl group">
                            Ver Planos Disponíveis
                            <i class="fas fa-arrow-down ml-2 group-hover:translate-y-1 transition-transform"></i>
                        </a>

                        @if($product->url_download)
                            <a href="{{ $product->url_download }}" target="_blank"
                                class="inline-flex items-center justify-center px-8 py-4 bg-gray-100 text-gray-800 font-bold rounded-full hover:bg-gray-200 transition shadow-lg hover:shadow-xl mt-4 md:mt-0 md:ml-4 group">
                                <i class="fas fa-download mr-2 group-hover:text-blue-600 transition-colors"></i> Baixar Demonstrativo
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Separator Transition -->
    <div style="height: 100px; overflow: hidden; margin-top: -1px;">
        <svg preserveAspectRatio="none" viewBox="0 0 1440 320" style="height: 100%; width: 100%;">
            <path fill="#f8f9fc" fill-opacity="1" d="M0,96L80,112C160,128,320,160,480,165.3C640,171,800,149,960,133.3C1120,117,1280,107,1360,101.3L1440,96L1440,320L1360,320C1280,320,1120,320,960,320C800,320,640,320,480,320C320,320,160,320,80,320L0,320Z"></path>
        </svg>
    </div>

    <!-- Base Pricing Section (Always Visible underneath custom HTML, or standalone) -->
    <div class="container mx-auto px-4 pb-20 pt-10" id="planos" style="background-color: #f8f9fc;">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-800 mb-2">Escolha o plano ideal</h2>
            <p class="text-gray-500">Desbloqueie todo o potencial do {{ $product->nome_software }}</p>
        </div>

        @if(count($plans) == 0)
            <div class="text-center text-secondary py-5">
                <i class="fas fa-info-circle fa-3x mb-3 text-secondary opacity-50"></i>
                <p>Nenhum plano disponível no momento para este produto.</p>
            </div>
        @else
            <div class="row justify-content-center">
                @php
                    $contactInfo = \App\Services\ResellerBranding::getContactInfo();
                @endphp

                @foreach($plans as $plan)
                    @php
                        // Obtém o preço final calculado para a revenda atual (via Accessor do Model)
                        $valorShow = $plan->preco_final;

                        $isFeatured = $plan->recorrencia == 12;
                        $periodName = match ($plan->recorrencia) {
                            1 => 'Mensal',
                            6 => 'Semestral',
                            12 => 'Anual',
                            default => $plan->recorrencia . ' Meses'
                        };
                    @endphp

                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card h-100 border-0 shadow-sm rounded-lg {{ $isFeatured ? 'border-accent border-2 shadow-lg scale-105 z-10' : '' }}" style="border-radius: 20px; transition: transform 0.3s; {{ $isFeatured ? 'transform: scale(1.05); border: 2px solid var(--color-accent) !important;' : '' }}">
                            
                            @if($isFeatured)
                                <div class="text-center" style="margin-top: -12px;">
                                    <span class="badge badge-warning px-3 py-2 text-uppercase font-weight-bold" style="border-radius: 50px; font-size: 0.75rem;">Mais Popular</span>
                                </div>
                            @endif

                            <div class="card-body p-5 text-center d-flex flex-column">
                                <h4 class="text-uppercase text-secondary font-weight-bold small tracking-widest mb-4">{{ $periodName }}</h4>
                                
                                <div class="mb-4">
                                    @if($contactInfo['has_payment'])
                                        <span class="h4 font-weight-bold text-dark">R$</span>
                                        <span class="display-4 font-weight-bold text-dark">{{ number_format($valorShow, 0, ',', '.') }}</span>
                                        <span class="h6 text-secondary font-weight-normal">,{{ sprintf('%02d', ($valorShow - floor($valorShow)) * 100) }}</span>
                                        <div class="small text-secondary mt-1">por {{ strtolower($periodName) }}</div>
                                    @else
                                        <div class="py-2">
                                            <span class="h4 font-weight-bold text-muted">Sob Consulta</span>
                                            <div class="small text-secondary mt-1">Entre em contato para valores</div>
                                        </div>
                                    @endif
                                </div>

                                <ul class="list-unstyled mb-5 text-left mx-auto" style="max-width: 200px;">
                                    <li class="mb-3 d-flex align-items-center">
                                        <i class="fas fa-check text-success mr-2"></i>
                                        <span class="text-secondary small">Acesso por <strong>{{ $plan->recorrencia }} meses</strong></span>
                                    </li>
                                    <li class="mb-3 d-flex align-items-center">
                                        <i class="fas fa-check text-success mr-2"></i>
                                        <span class="text-secondary small">Recursos Completos</span>
                                    </li>
                                    <li class="mb-3 d-flex align-items-center">
                                        <i class="fas fa-check text-success mr-2"></i>
                                        <span class="text-secondary small">Suporte 24/7</span>
                                    </li>
                                </ul>

                                <div class="mt-auto">
                                    @if($contactInfo['has_payment'])
                                        <a href="{{ route('checkout.start', $plan->id) }}" class="btn {{ $isFeatured ? 'btn-primary' : 'btn-outline-primary' }} btn-block py-3 font-weight-bold rounded-pill shadow-sm">
                                            Assinar Agora
                                        </a>
                                    @else
                                        @if(!empty($contactInfo['whatsapp']))
                                            <a href="https://wa.me/55{{ $contactInfo['whatsapp'] }}?text={{ urlencode('Olá, gostaria de saber mais sobre o plano ' . $periodName . ' do ' . $product->nome_software) }}" target="_blank" class="btn btn-success btn-block py-3 font-weight-bold rounded-pill shadow-sm">
                                                <i class="fab fa-whatsapp mr-2"></i> Falar no WhatsApp
                                            </a>
                                        @else
                                            <button disabled class="btn btn-secondary btn-block py-3 font-weight-bold rounded-pill">Indisponível</button>
                                        @endif
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