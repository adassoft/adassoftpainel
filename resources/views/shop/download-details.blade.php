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

                    <a href="{{ asset('storage/' . $download->arquivo_path) }}"
                        class="btn btn-primary btn-lg rounded-pill px-5 py-3 font-weight-bold shadow-lg">
                        <i class="fas fa-cloud-download-alt mr-2"></i> Baixar Agora
                    </a>
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
                                    <div class="font-weight-bold">{{ $download->tamanho_arquivo ?: '-' }}</div>
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
@endsection