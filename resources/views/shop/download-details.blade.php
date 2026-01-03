@extends('layouts.app')

@section('title', $download->titulo . ' - Download Adassoft')

@section('extra-css')
    <style>
        .details-wrapper {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            padding: 40px;
            margin-top: 40px;
        }

        .file-icon-large {
            width: 120px;
            height: 120px;
            background: #f8f9fc;
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            color: #4e73df;
            margin-bottom: 25px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f8f9fc;
            border-radius: 12px;
            margin-bottom: 15px;
        }

        .meta-icon {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: #4e73df;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
        }
    </style>
@endsection

@section('content')
    <div class="container py-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-transparent p-0 mb-4">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('downloads') }}">Downloads</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $download->titulo }}</li>
            </ol>
        </nav>

        <div class="details-wrapper">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <div class="file-icon-large">
                        <i class="fas fa-file-download"></i>
                    </div>
                    <h1 class="font-weight-bold text-gray-900 mb-3">{{ $download->titulo }}</h1>
                    <div class="text-muted lead mb-4">
                        {{ $download->descricao ?: 'Nenhuma descrição detalhada disponível.' }}
                    </div>

                    @php
                        // Map icons
                        $osIcons = [
                            'windows' => 'fab fa-windows',
                            'linux' => 'fab fa-linux',
                            'mac' => 'fab fa-apple',
                            'ios' => 'fab fa-app-store-ios',
                            'android' => 'fab fa-android',
                            'any' => 'fas fa-download'
                        ];
                    @endphp

                    @if(!($hasAccess ?? true))
                        @if($download->is_paid)
                            <button onclick="alert('O módulo de Checkout será ativado em breve. Entre em contato para adquirir agora.')"
                                class="btn btn-success btn-lg rounded-pill px-5 py-3 font-weight-bold shadow-lg mb-2">
                                <i class="fas fa-shopping-cart mr-2"></i> Comprar Agora por R$ {{ number_format($download->preco, 2, ',', '.') }}
                            </button>
                            <p class="text-muted small"><i class="fas fa-lock mr-1"></i> Acesso liberado imediatamente após o pagamento.</p>
                        @elseif($download->requires_login)
                            <a href="{{ route('login') }}"
                                class="btn btn-primary btn-lg rounded-pill px-5 py-3 font-weight-bold shadow-lg">
                                <i class="fas fa-sign-in-alt mr-2"></i> Fazer Login para Baixar
                            </a>
                            <p class="text-muted small mt-2">Você precisa estar logado para acessar este arquivo.</p>
                        @endif
                    @else
                        @if(isset($latestByOs) && $latestByOs->count() > 1)
                            <div class="mb-4">
                                <h5 class="font-weight-bold mb-3">Selecione sua Plataforma:</h5>
                                <div class="d-flex flex-wrap" style="gap: 10px;">
                                    @foreach($latestByOs as $os => $ver)
                                        <a href="{{ route('downloads.version.file', $ver->id) }}"
                                            class="btn btn-outline-primary rounded-pill px-4 py-2 font-weight-bold shadow-sm d-flex align-items-center">
                                            <i class="{{ $osIcons[$os] ?? 'fas fa-download' }} mr-2 fa-lg"></i>
                                            {{ ucfirst($os) }} <small class="text-muted ml-2">({{ $ver->versao }})</small>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            @php
                                $downloadUrl = route('downloads.file', $download->slug ?: $download->id);
                            @endphp

                            <a href="{{ $downloadUrl }}" target="_blank"
                                class="btn btn-primary btn-lg rounded-pill px-5 py-3 font-weight-bold shadow-lg">
                                <i class="fas fa-cloud-download-alt mr-2"></i> Baixar Agora
                            </a>
                        @endif
                    @endif

                    @if(isset($software))
                        <div class="mt-5 pt-4 border-top">
                            <p class="text-muted small text-uppercase font-weight-bold mb-2">Software Relacionado</p>
                            <h5 class="font-weight-bold text-dark">{{ $software->nome_software }}</h5>
                            <a href="{{ route('product.show', $software->slug ?? $software->id) }}"
                                class="btn btn-outline-primary btn-sm rounded-pill mt-2">
                                <i class="fas fa-box-open mr-1"></i> Ver detalhes do Produto
                            </a>
                        </div>
                    @endif
                </div>

                <div class="col-lg-4 offset-lg-1 mt-5 mt-lg-0">
                    <div class="card border-0 bg-light rounded-xl">
                        <div class="card-body p-4">
                            <h5 class="font-weight-bold mb-4">Informações do Arquivo</h5>

                            <div class="meta-item">
                                <div class="meta-icon"><i class="fas fa-history"></i></div>
                                <div>
                                    <div class="small text-muted">Versão Atual</div>
                                    <div class="font-weight-bold">{{ $download->versao ?: 'N/A' }}</div>
                                </div>
                            </div>

                            <div class="meta-item">
                                <div class="meta-icon"><i class="fas fa-weight-hanging"></i></div>
                                <div>
                                    <div class="small text-muted">Tamanho</div>
                                    <div class="font-weight-bold">
                                        {{ $download->tamanho ?: ($download->tamanho_arquivo ?? '-') }}
                                    </div>
                                </div>
                            </div>

                            <div class="meta-item">
                                <div class="meta-icon"><i class="fas fa-calendar-alt"></i></div>
                                <div>
                                    <div class="small text-muted">Última Atualização</div>
                                    <div class="font-weight-bold">
                                        {{ $download->data_atualizacao ? $download->data_atualizacao->format('d/m/Y') : '-' }}
                                    </div>
                                </div>
                            </div>

                            <div class="meta-item">
                                <div class="meta-icon"><i class="fas fa-download"></i></div>
                                <div>
                                    <div class="small text-muted">Downloads Realizados</div>
                                    <div class="font-weight-bold">{{ number_format($download->contador ?? 0, 0, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                            
                            @if($download->is_paid && !empty($download->preco))
                                <div class="meta-item bg-white border border-success shadow-sm mt-3">
                                    <div class="meta-icon text-success"><i class="fas fa-tag"></i></div>
                                    <div>
                                        <div class="small text-muted">Preço</div>
                                        <div class="font-weight-bold text-success h4 mb-0">R$ {{ number_format($download->preco, 2, ',', '.') }}</div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Histórico de Versões Integrado --}}
            @if(isset($versions) && $versions->count() > 0)
                <div class="mt-5 pt-4 border-top text-left">
                    <h5 class="font-weight-bold mb-4 text-center text-sm-left">
                        <i class="fas fa-history mr-2 text-muted"></i> Histórico de Versões e Arquivos
                    </h5>

                    <div class="list-group list-group-flush shadow-sm rounded overflow-hidden">
                        @foreach($versions as $v)
                            <div
                                class="list-group-item p-3 d-flex flex-column flex-sm-row align-items-center justify-content-between hover-bg-light transition-all">
                                <div class="d-flex align-items-center mb-2 mb-sm-0">
                                    <div class="mr-3 text-center" style="width: 40px;">
                                        @php
                                            $osIcons = [
                                                'windows' => 'fab fa-windows text-primary',
                                                'linux' => 'fab fa-linux text-warning',
                                                'mac' => 'fab fa-apple text-dark',
                                                'android' => 'fab fa-android text-success',
                                                'ios' => 'fab fa-app-store-ios text-info',
                                                'any' => 'fas fa-file-archive text-secondary'
                                            ];
                                            $icon = $osIcons[$v->sistema_operacional ?? 'any'] ?? $osIcons['any'];
                                        @endphp
                                        <i class="{{ $icon }} fa-2x"></i>
                                    </div>
                                    <div>
                                        <h6 class="font-weight-bold mb-0 text-dark">
                                            Versão {{ $v->versao }}
                                            @if($v->sistema_operacional && $v->sistema_operacional !== 'any')
                                                <small class="text-muted ml-1">({{ ucfirst($v->sistema_operacional) }})</small>
                                            @endif
                                        </h6>
                                        <div class="small text-muted">
                                            <span class="mr-3"><i class="far fa-calendar-alt mr-1"></i>
                                                {{ $v->data_lancamento ? \Carbon\Carbon::parse($v->data_lancamento)->format('d/m/Y') : '-' }}</span>
                                            <span><i class="far fa-hdd mr-1"></i> {{ $v->tamanho }}</span>
                                        </div>
                                        @if($v->changelog)
                                            <div class="small text-muted mt-1 font-italic">
                                                "{{ \Illuminate\Support\Str::limit($v->changelog, 60) }}"</div>
                                        @endif
                                    </div>
                                </div>
                                
                                @if($hasAccess ?? true)
                                    <a href="{{ route('downloads.version.file', $v->id) }}"
                                        class="btn btn-sm btn-outline-primary rounded-pill px-3 font-weight-bold mt-2 mt-sm-0">
                                        <i class="fas fa-download mr-1"></i> Baixar
                                    </a>
                                @else
                                    <button disabled class="btn btn-sm btn-light text-muted rounded-pill px-3 font-weight-bold mt-2 mt-sm-0 border">
                                        <i class="fas fa-lock mr-1"></i> Bloqueado
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>
@endsection