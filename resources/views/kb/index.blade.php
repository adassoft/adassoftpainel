@extends('layouts.app')

@section('title', 'Central de Ajuda')

@section('extra-css')
    <style>
        .kb-header {
            background: linear-gradient(135deg, var(--primary-gradient-start, #4e73df) 0%, var(--primary-gradient-end, #224abe) 100%);
            padding: 4rem 0;
            color: white;
            margin-bottom: 3rem;
        }

        .kb-card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
            height: 100%;
            border-top: 3px solid transparent;
            /* Para a cor da categoria */
        }

        .kb-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .kb-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .cat-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            margin-right: 15px;
            background-color: #f8f9fa;
            color: #5a5c69;
        }

        .cat-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: #2e384d;
            line-height: 1.2;
        }

        .article-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .article-list li {
            margin-bottom: 1rem;
            display: flex;
            align-items: flex-start;
        }

        .article-icon {
            color: #858796;
            margin-right: 10px;
            margin-top: 3px;
        }

        .article-link {
            color: #5a5c69;
            text-decoration: none;
            font-size: 0.95rem;
            transition: color 0.2s;
            line-height: 1.4;
        }

        .article-link:hover {
            color: var(--color-accent, #4e73df);
            text-decoration: none;
        }

        /* Cores das Bordas baseadas no nome da cor (fallback simples) */
        .border-primary {
            border-top-color: #4e73df !important;
        }

        .border-success {
            border-top-color: #1cc88a !important;
        }

        .border-warning {
            border-top-color: #f6c23e !important;
        }

        .border-danger {
            border-top-color: #e74a3b !important;
        }

        .border-info {
            border-top-color: #36b9cc !important;
        }

        .border-secondary {
            border-top-color: #858796 !important;
        }
    </style>
@endsection

@section('content')
    <!-- Hero Search -->
    <div class="kb-header text-center">
        <div class="container">
            <h1 class="font-weight-bold mb-3">Como podemos ajudar?</h1>
            <p class="lead mb-4 opacity-80">Pesquise em nossa base de conhecimento ou navegue pelas categorias abaixo.</p>

            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <form action="{{ route('kb.index') }}" method="GET">
                        <div class="input-group input-group-lg shadow-sm">
                            <input type="text" name="q" class="form-control border-0" placeholder="Digite sua dúvida..."
                                aria-label="Buscar" value="{{ $query ?? '' }}">
                            <div class="input-group-append">
                                <button class="btn btn-light text-primary font-weight-bold px-4"
                                    type="submit">Buscar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if(isset($searchResults))
        <div class="container pb-5">
            <h3 class="mb-4">Resultados para: "{{ $query }}"</h3>
            @if($searchResults->count() > 0)
                <div class="row">
                    @foreach ($searchResults as $article)
                        <div class="col-md-6 col-lg-4 mb-4">
                            <a href="{{ route('kb.show', $article->slug ?? 'artigo-' . $article->id) }}"
                                class="card kb-card h-100 text-decoration-none">
                                <div class="card-body p-4 d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="cat-icon text-primary" style="width: 48px; height: 48px;">
                                            <x-heroicon-o-document-text class="w-6 h-6" style="width: 24px; height: 24px;" />
                                        </div>
                                        <span class="small text-muted">
                                            {{ $article->updated_at->format('d/m/Y') }}
                                        </span>
                                    </div>

                                    <h5 class="mb-2 text-dark font-weight-bold">{{ $article->title }}</h5>

                                    <p class="small text-muted mb-4 flex-grow-1"
                                        style="overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;">
                                        {{ Str::limit(html_entity_decode(strip_tags($article->content)), 120) }}
                                    </p>

                                    <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                                        <span class="font-weight-bold small text-primary">Ler artigo</span>
                                        <x-heroicon-m-arrow-right class="w-4 h-4 text-primary" style="width: 16px; height: 16px;" />
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
                <div class="text-center mt-4">
                    <a href="{{ route('kb.index') }}" class="btn btn-outline-secondary">Limpar busca</a>
                </div>
            @else
                <div class="text-center py-5">
                    <div class="mb-3 text-muted">
                        <x-heroicon-o-magnifying-glass class="w-12 h-12 mx-auto" style="width: 48px; height: 48px; opacity: 0.5;" />
                    </div>
                    <h3 class="text-gray-500 mb-2">Nenhum resultado encontrado</h3>
                    <p class="text-muted mb-4">Tente usar outros termos ou navegue pelas categorias abaixo.</p>
                    <a href="{{ route('kb.index') }}" class="btn btn-primary">
                        Ver todas as categorias
                    </a>
                </div>
            @endif
            <hr class="my-5">
        </div>
    @endif

    <!-- Categorias Grid -->
    <div class="container pb-5">
        @if($categories->count() > 0)
            <div class="row">
                @foreach($categories as $category)
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card kb-card border-{{ $category->color }}">
                            <div class="card-body p-4">

                                <!-- Header do Card -->
                                <div class="kb-card-header">
                                    <div class="d-flex align-items-center">
                                        <div
                                            class="cat-icon text-{{ in_array($category->color ?? 'primary', ['primary', 'success', 'danger', 'warning', 'info', 'secondary']) ? $category->color : 'primary' }}">
                                            @if(str_contains($category->icon ?? '', '<svg'))
                                                {!! $category->icon !!}
                                            @else
                                                @php
                                                    try {
                                                        echo svg($category->icon ?? 'heroicon-o-folder', 'w-8 h-8')->toHtml();
                                                    } catch (\Exception $e) {
                                                        echo '<i class="fas fa-folder fa-lg"></i>';
                                                    }
                                                @endphp
                                            @endif
                                        </div>
                                        <div class="cat-title">
                                            <a href="{{ route('kb.category', $category->slug) }}"
                                                style="color: inherit; text-decoration: none;">
                                                {{ $category->name }}
                                            </a>
                                        </div>
                                    </div>
                                    <span class="badge badge-light badge-pill text-muted">{{ $category->articles_count }}</span>
                                </div>

                                <!-- Lista de Artigos -->
                                @if($category->articles->count() > 0)
                                    <ul class="article-list">
                                        @foreach($category->articles as $article)
                                            <li>
                                                <i class="far fa-file-alt article-icon"></i>
                                                <a href="{{ route('kb.show', $article->slug ?? 'artigo-' . $article->id) }}"
                                                    class="article-link">
                                                    {{ $article->title }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                    @if($category->articles_count > 5)
                                        <div class="mt-3 text-right">
                                            <a href="{{ route('kb.category', $category->slug) }}"
                                                class="small font-weight-bold text-primary">Ver todos &rarr;</a>
                                        </div>
                                    @endif
                                @else
                                    <p class="text-muted small">Nenhum artigo público nesta categoria ainda.</p>
                                @endif

                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-5">
                <h3 class="text-gray-500">Nenhuma categoria encontrada.</h3>
                <p>A base de conhecimento está sendo construída.</p>
            </div>
        @endif
    </div>
@endsection