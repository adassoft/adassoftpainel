@extends('layouts.app')

@section('title', $category->name . ' - Central de Ajuda')

@section('extra-css')
    <style>
        .kb-category-header {
            background: linear-gradient(135deg, var(--primary-gradient-start, #4e73df) 0%, var(--primary-gradient-end, #224abe) 100%);
            padding: 4rem 0;
            color: white;
            margin-bottom: 3rem;
        }

        .kb-article-card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
            display: flex;
            flex-direction: column;
            text-decoration: none !important;
            /* Force no default link style */
        }

        .kb-article-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        /* Garantir que o texto dentro do card tenha cores normais e não de link */
        .kb-article-card * {
            color: #5a5c69;
            /* text-gray-600 */
        }

        .kb-article-card h5 {
            color: #2e384d;
            /* Heading color */
            font-weight: 700;
        }

        .kb-article-card:hover h5,
        .kb-article-card:hover .read-more {
            color: #4e73df;
            /* primary color */
        }

        .article-icon-wrapper {
            background-color: #f8f9fa;
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            color: #4e73df;
        }

        .article-meta {
            font-size: 0.85rem;
            color: #858796;
        }

        .cat-icon-lg {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            color: white;
        }

        .cat-icon-lg svg {
            width: 48px;
            height: 48px;
        }
    </style>
@endsection

@section('content')
    <!-- Header da Categoria -->
    <div class="kb-category-header text-center">
        <div class="container">
            <!-- Icone da Categoria -->
            <div class="mb-4">
                <div class="cat-icon-lg">
                    @if (str_contains($category->icon ?? '', '<svg'))
                        {!! $category->icon !!}
                    @else
                        @php
                            try {
                                echo svg($category->icon ?? 'heroicon-o-folder', 'w-12 h-12')->toHtml();
                            } catch (\Exception $e) {
                                echo '<i class="fas fa-folder fa-3x"></i>';
                            }
                        @endphp
                    @endif
                </div>
            </div>

            <h1 class="font-weight-bold mb-3">{{ $category->name }}</h1>
            <p class="lead mb-4 opacity-80" style="opacity: 0.9;">{{ $category->description }}</p>

            <div class="mt-4">
                <a href="{{ route('kb.index') }}"
                    class="btn btn-outline-light btn-sm font-weight-bold rounded-pill px-4 py-2 d-inline-flex align-items-center">
                    <x-heroicon-m-arrow-left class="w-4 h-4 mr-2" style="width: 16px; height: 16px; margin-right: 0.5rem;" /> Voltar para a Central de Ajuda
                </a>
            </div>
        </div>
    </div>

    <!-- Lista de Artigos (Cards) -->
    <div class="container pb-5">
        @if ($articles->count() > 0)
            <div class="row">
                @foreach ($articles as $article)
                    <div class="col-md-6 col-lg-4 mb-4">
                        <a href="{{ route('kb.show', $article->slug ?? 'artigo-' . $article->id) }}"
                            class="card kb-article-card h-100">
                            <div class="card-body p-4 d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="article-icon-wrapper text-primary">
                                        <x-heroicon-o-document-text class="w-6 h-6" style="width: 24px; height: 24px;" />
                                    </div>
                                    <span class="article-meta">
                                        {{ $article->updated_at->format('d/m/Y') }}
                                    </span>
                                </div>

                                <h5 class="mb-2">{{ $article->title }}</h5>

                                <p class="small mb-4 flex-grow-1"
                                    style="overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;">
                                    {{ Str::limit(html_entity_decode(strip_tags($article->content)), 120) }}
                                </p>

                                <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                                    <span class="font-weight-bold small read-more">Ler artigo</span>
                                    <x-heroicon-m-arrow-right class="w-4 h-4 read-more" style="width: 16px; height: 16px;" />
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>

            <div class="mt-5 d-flex justify-content-center">
                {{ $articles->links() }}
                {{-- Nota: Se a paginação padrão do Laravel usar Tailwind, pode precisar customizar para Bootstrap.
                Geralmente $articles->links('pagination::bootstrap-4') resolve. --}}
            </div>
        @else
            <div class="text-center py-5">
                <div class="mb-3 text-muted">
                    <x-heroicon-o-document-magnifying-glass class="w-12 h-12 mx-auto"
                        style="width: 48px; height: 48px; opacity: 0.5;" />
                </div>
                <h3 class="text-gray-500 mb-2">Nenhum artigo encontrado</h3>
                <p class="text-muted mb-4">Não há artigos públicos disponíveis nesta categoria no momento.</p>
                <a href="{{ route('kb.index') }}" class="btn btn-primary">
                    Voltar ao Início
                </a>
            </div>
        @endif
    </div>
@endsection