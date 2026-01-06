@extends('layouts.app')

@section('title', $page->title)

@section('extra-css')
    <style>
        /* Estilo estilo 'Medium/Blog' para leitura agradável */
        .legal-content {
            font-family: 'Nunito', sans-serif;
            /* Ou 'Inter' se tiver */
            font-size: 1.1rem;
            line-height: 1.8;
            color: #2d3748;
            /* Gray-800 */
        }

        .legal-content h1,
        .legal-content h2,
        .legal-content h3 {
            color: #1a202c;
            /* Gray-900 */
            font-weight: 700;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }

        .legal-content h1 {
            font-size: 2rem;
        }

        .legal-content h2 {
            font-size: 1.75rem;
            border-bottom: 2px solid #edf2f7;
            padding-bottom: 0.5rem;
        }

        .legal-content h3 {
            font-size: 1.5rem;
        }

        .legal-content p {
            margin-bottom: 1.5rem;
        }

        .legal-content ul,
        .legal-content ol {
            margin-bottom: 1.5rem;
            padding-left: 2rem;
        }

        .legal-content li {
            margin-bottom: 0.5rem;
        }

        .legal-content a {
            color: var(--color-accent, #4e73df);
            text-decoration: underline;
        }

        .legal-content a:hover {
            color: var(--primary-gradient-end);
        }

        .header-legal {
            background: linear-gradient(135deg, var(--primary-gradient-start, #4e73df) 0%, var(--primary-gradient-end, #224abe) 100%);
            color: white;
            padding: 4rem 0 3rem;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
        }

        /* Pattern opcional de fundo */
        .header-legal::before {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 10%, transparent 10%);
            background-size: 20px 20px;
            opacity: 0.3;
            transform: rotate(30deg);
        }

        .card-legal {
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border-radius: 12px;
            margin-top: -3rem;
            /* Efeito de sobreposição ao Header */
        }

        .meta-info {
            font-size: 0.9rem;
            color: #718096;
        }
    </style>
@endsection

@section('content')
    <!-- Header Hero -->
    <div class="header-legal text-center">
        <div class="container position-relative">
            <span class="badge badge-light text-uppercase font-weight-bold px-3 py-2 mb-3 shadow-sm"
                style="color: var(--primary-gradient-start)">
                Jurídico & Compliance
            </span>
            <h1 class="font-weight-bold display-4">{{ $page->title }}</h1>
            <p class="lead opacity-80 mt-2">Transparência e clareza para nossos usuários.</p>
        </div>
    </div>

    <!-- Conteúdo Principal -->
    <div class="container pb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-8">
                <div class="card card-legal">
                    <div class="card-body p-5">

                        <!-- Metadados Topo -->
                        <div class="d-flex justify-content-between align-items-center mb-5 pb-3 border-bottom">
                            <span class="meta-info">
                                <i class="far fa-clock mr-1"></i> Atualizado em:
                                <strong>{{ $page->updated_at->format('d/m/Y') }}</strong>
                            </span>
                            {{-- Botão Imprimir (Opcional) --}}
                            <button onclick="window.print()"
                                class="btn btn-sm btn-outline-secondary border-0 rounded-circle" title="Imprimir">
                                <i class="fas fa-print"></i>
                            </button>
                        </div>

                        <!-- Conteúdo Rico -->
                        <div class="legal-content">
                            {!! $page->content !!}
                        </div>

                        <!-- Rodapé do Documento -->
                        <div class="mt-5 pt-4 border-top bg-light rounded p-4 text-center">
                            <p class="mb-0 text-muted small">
                                Este documento é efetivo a partir de {{ $page->updated_at->format('d/m/Y') }}.
                                Em caso de dúvidas, entre em contato com nosso <a
                                    href="mailto:{{ \App\Services\ResellerBranding::getContactInfo()['email'] ?? 'suporte' }}">suporte</a>.
                            </p>
                            <p class="mt-2 text-muted small font-italic">
                                © {{ date('Y') }}
                                {{ \App\Services\ResellerBranding::getCurrent()['nome_sistema'] ?? 'Sistema' }}. Todos os
                                direitos reservados.
                            </p>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection