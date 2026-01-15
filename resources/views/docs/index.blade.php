@extends('layouts.app')

@section('title', 'Documentação SDK')

@section('content')
    <div class="container-fluid pb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Page Heading -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-4">
                    <h1 class="h3 mb-0 text-gray-800">📚 Documentação do Desenvolvedor</h1>
                </div>

                <div class="row">
                    <!-- Delphi Card -->
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            SDK Desktop</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">Delphi VCL</div>
                                        <p class="mt-2 text-sm text-gray-600">Integração nativa para aplicações VCL
                                            clássicas, com componentes visuais prontos.</p>
                                        <a href="{{ route('docs.delphi') }}" class="btn btn-primary btn-sm mt-2">Ver
                                            Documentação</a>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-boxes fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Java Card -->
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Multiplataforma</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">Java SDK</div>
                                        <p class="mt-2 text-sm text-gray-600">Compatível com Java 11+. Zero dependências
                                            externas (Apenas JDK).</p>
                                        <a href="{{ route('docs.java') }}" class="btn btn-warning btn-sm mt-2">Ver
                                            Documentação</a>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fab fa-java fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lazarus Card -->
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Open Source</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">Lazarus / FPC</div>
                                        <p class="mt-2 text-sm text-gray-600">Para aplicações Free Pascal. Roda em Windows,
                                            Linux e macOS.</p>
                                        <a href="{{ route('docs.lazarus') }}" class="btn btn-info btn-sm mt-2">Ver
                                            Documentação</a>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-code fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Download Center -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Central de Downloads</h6>
                    </div>
                    <div class="card-body">
                        <p>Aqui você encontra todos os arquivos necessários para começar a integração.</p>
                        <ul>
                            <li><a href="{{ url('downloads/sdk_delphi.zip') }}">SDK Delphi (.zip)</a> - Inclui sources e
                                demos VCL.</li>
                            <li><a href="{{ url('downloads/sdk_java.zip') }}">SDK Java (.zip)</a> - Inclui .jar e fontes.
                            </li>
                            <li><a href="{{ url('downloads/sdk_lazarus.zip') }}">SDK Lazarus (.zip)</a> - Unidades
                                compatíveis com Linux/Windows.</li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection