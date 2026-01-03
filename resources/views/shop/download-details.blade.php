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
                            // Sempre usa a rota interna para garantir a contagem
                            // O Controller decide se faz download direto ou redireciona
                            $downloadUrl = route('downloads.file', $download->slug ?: $download->id);
                        @endphp

                        <a href="{{ $downloadUrl }}" target="_blank"
                            class="btn btn-primary btn-lg rounded-pill px-5 py-3 font-weight-bold shadow-lg">
                            <i class="fas fa-cloud-download-alt mr-2"></i> Baixar Agora
                        </a>
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(isset($versions) && $versions->count() > 0)
        <div class="row mt-5">
            <div class="col-12">
                <h4 class="font-weight-bold mb-4 border-bottom pb-2">Histórico de Versões</h4>
                <div class="table-responsive bg-white rounded-lg shadow-sm border">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th class="py-3 pl-4">Versão</th>
                                <th class="py-3">Data de Lançamento</th>
                                <th class="py-3">Tamanho</th>
                                <th class="py-3">Notas (Changelog)</th>
                                <th class="py-3 text-right pr-4">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($versions as $v)
                                <tr>
                                    <td class="font-weight-bold pl-4 text-dark">{{ $v->versao }}</td>
                                    <td>{{ $v->data_lancamento ? \Carbon\Carbon::parse($v->data_lancamento)->format('d/m/Y') : '-' }}
                                    </td>
                                    <td>{{ $v->tamanho }}</td>
                                    <td class="text-muted small">{{ \Illuminate\Support\Str::limit($v->changelog, 50) ?: '-' }}</td>
                                    <td class="text-right pr-4">
                                        <a href="{{ route('downloads.version.file', $v->id) }}"
                                            class="btn btn-sm btn-outline-primary rounded-pill">
                                            <i class="fas fa-download mr-1"></i> Baixar
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
    </div>
@endsection