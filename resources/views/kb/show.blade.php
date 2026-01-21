@extends('layouts.app')

@section('title', $article->title)

@section('extra-css')
    <style>
        /* Reusando estilos do Legal Page + Ajustes KB */
        .kb-content {
            font-family: 'Nunito', sans-serif;
            font-size: 1.05rem;
            line-height: 1.8;
            color: #2d3748;
        }

        .kb-content h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a202c;
            margin-top: 2rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #edf2f7;
        }

        .kb-content h3 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-top: 1.5rem;
        }

        .kb-content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin: 1.5rem 0;
        }

        .kb-content a {
            color: var(--color-accent, #4e73df);
            text-decoration: underline;
        }

        .kb-breadcrumb {
            background: transparent;
            padding: 0;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .sidebar-kb {
            position: sticky;
            top: 2rem;
        }

        .sidebar-link {
            display: block;
            padding: 0.5rem 0;
            color: #718096;
            text-decoration: none;
            border-left: 2px solid transparent;
            padding-left: 1rem;
            transition: all 0.2s;
        }

        .sidebar-link:hover {
            color: var(--color-accent, #4e73df);
            border-left-color: #cbd5e0;
            text-decoration: none;
        }

        .sidebar-link.active {
            color: var(--color-accent, #4e73df);
            border-left-color: var(--color-accent, #4e73df);
            font-weight: 600;
        }

        /* Fallback para Embed Responsive (Bootstrap 4 style) */
        .embed-responsive {
            position: relative;
            display: block;
            width: 100%;
            padding: 0;
            overflow: hidden;
        }

        .embed-responsive::before {
            display: block;
            content: "";
        }

        .embed-responsive-16by9::before {
            padding-top: 56.25%;
        }

        .embed-responsive .embed-responsive-item,
        .embed-responsive iframe,
        .embed-responsive embed,
        .embed-responsive object,
        .embed-responsive video {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }

        /* Rich Text Styles for FAQ */
        .kb-faq-content ul { padding-left: 20px; list-style-type: disc; margin-bottom: 1rem; }
        .kb-faq-content ol { padding-left: 20px; list-style-type: decimal; margin-bottom: 1rem; }
        .kb-faq-content p:last-child { margin-bottom: 0; }
        .kb-faq-content a { color: var(--color-accent, #4e73df); text-decoration: underline; }
    </style>
@endsection

@section('content')
    <script type="application/ld+json">
                {!! $article->json_ld !!}
            </script>

    <div class="bg-gray-100 py-5 min-vh-100" style="background-color: #f8f9fc;">
        <div class="container">

            <!-- Breadcrumbs -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb kb-breadcrumb mb-4">
                    <li class="breadcrumb-item"><a href="{{ route('kb.index') }}">Central de Ajuda</a></li>
                    @if($contextCategory)
                        <li class="breadcrumb-item"><a
                                href="{{ route('kb.category', $contextCategory->slug) }}">{{ $contextCategory->name }}</a>
                        </li>
                    @endif
                    <li class="breadcrumb-item active" aria-current="page">{{ Str::limit($article->title, 30) }}</li>
                </ol>
            </nav>

            <div class="row">
                <!-- Conteúdo Principal -->
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 rounded-lg mb-4">
                        <div class="card-body p-5">
                            <h1 class="font-weight-bold text-gray-900 mb-4">{{ $article->title }}</h1>

                            <div class="d-flex align-items-center text-muted small mb-5 pb-3 border-bottom">
                                <span class="mr-4"><i class="far fa-clock mr-1"></i> Atualizado:
                                    {{ $article->updated_at->format('d/m/Y') }}</span>
                                @foreach($article->categories as $cat)
                                    <a href="{{ route('kb.category', $cat->slug) }}" class="text-decoration-none mr-2">
                                        <span
                                            class="badge badge-light text-uppercase tracking-wide px-2 py-1 text-primary border">
                                            {{ $cat->name }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>

                            <div class="kb-content mb-5">
                                {!! $article->processed_content !!}
                            </div>

                            @if($article->video_url)
                                @php
                                    preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/\s]{11})%i', $article->video_url, $match);
                                    $videoId = $match[1] ?? null;
                                @endphp

                                @if($videoId)
                                    <div class="mb-4">
                                        <h5 class="font-weight-bold mb-3"><i class="fab fa-youtube text-danger mr-2"></i> Vídeo
                                            Tutorial</h5>
                                        <div class="embed-responsive embed-responsive-16by9 rounded shadow-sm"
                                            style="border-radius: 12px; overflow: hidden;">
                                            <iframe class="embed-responsive-item" src="https://www.youtube.com/embed/{{ $videoId }}"
                                                allowfullscreen></iframe>
                                        </div>
                                    </div>
                                @endif
                            @endif

                            <!-- Feedback Section -->
                            <div class="mt-5 pt-4 border-top text-center" id="feedback-section">
                                <p class="text-muted mb-3">Isso foi útil?</p>

                                <div id="feedback-buttons">
                                    <button class="btn btn-outline-success btn-sm px-4 mr-2 rounded-pill"
                                        onclick="vote('helpful')">
                                        <i class="far fa-thumbs-up"></i> Sim <span class="ml-1"
                                            id="count-helpful">({{ $article->helpful_count }})</span>
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm px-4 rounded-pill"
                                        onclick="vote('not_helpful')">
                                        <i class="far fa-thumbs-down"></i> Não
                                    </button>
                                </div>

                                <div id="feedback-thankyou" style="display: none;"
                                    class="text-success font-weight-bold mt-3">
                                    <i class="fas fa-check-circle"></i> Obrigado pelo seu feedback!
                                </div>
                            </div>

                            <!-- Author Bio (E-E-A-T) -->
                            @if($article->author && $article->author->bio)
                                <div class="mt-5 p-4 bg-light rounded shadow-sm border-left-primary"
                                    style="background-color: #f8f9fc; border-left: 4px solid var(--color-accent, #4e73df);">
                                    <div class="row align-items-center">
                                        <div class="col-md-2 text-center mb-3 mb-md-0">
                                            <img src="{{ $article->author->foto ? asset('storage/' . $article->author->foto) : 'https://ui-avatars.com/api/?name=' . urlencode($article->author->name) . '&background=random' }}"
                                                class="rounded-circle img-fluid shadow-sm"
                                                style="width: 80px; height: 80px; object-fit: cover;"
                                                alt="{{ $article->author->name }}">
                                        </div>
                                        <div class="col-md-10">
                                            <h6 class="font-weight-bold mb-1 text-dark">{{ $article->author->name }}</h6>
                                            @if($article->author->job_title)
                                                <p class="small text-uppercase text-primary font-weight-bold mb-2"
                                                    style="font-size: 0.75rem; letter-spacing: 1px;">
                                                    {{ $article->author->job_title }}</p>
                                            @endif
                                            <p class="small text-muted mb-0" style="line-height: 1.6;">
                                                {!! nl2br(e($article->author->bio)) !!}
                                            </p>
                                            @if($article->author->linkedin_url)
                                                <a href="{{ $article->author->linkedin_url }}" target="_blank"
                                                    class="mt-2 d-inline-block small text-primary font-weight-bold">
                                                    <i class="fab fa-linkedin"></i> Ver Perfil Profissional
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- FAQ Section -->
                            @if(!empty($article->faq) && is_array($article->faq))
                                <div class="mt-5 mb-5">
                                    <h3 class="font-weight-bold mb-4">Perguntas Frequentes</h3>
                                    <div class="accordion" id="faqAccordion">
                                        @foreach($article->faq as $index => $item)
                                            @if(!empty($item['question']) && !empty($item['answer']))
                                                <div class="card border-0 mb-3 shadow-sm rounded-lg overflow-hidden">
                                                    <div class="card-header bg-white p-0" id="heading{{ $index }}">
                                                        <h2 class="mb-0">
                                                            <button class="btn btn-link btn-block text-left font-weight-bold text-dark p-4 d-flex justify-content-between align-items-center text-decoration-none" 
                                                                    type="button" 
                                                                    data-toggle="collapse" 
                                                                    data-target="#collapse{{ $index }}" 
                                                                    aria-expanded="false" 
                                                                    aria-controls="collapse{{ $index }}"
                                                                    style="text-decoration: none; box-shadow: none;">
                                                                {{ $item['question'] }}
                                                                <i class="fas fa-chevron-down text-gray-400"></i>
                                                            </button>
                                                        </h2>
                                                    </div>

                                                    <div id="collapse{{ $index }}" class="collapse" aria-labelledby="heading{{ $index }}" 
                                                         data-parent="#faqAccordion">
                                                        <div class="card-body bg-light text-muted p-4 kb-faq-content">
                                                            {!! $item['answer'] !!}
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif



                            <script>
                                function vote(type) {
                                    const buttons = document.getElementById('feedback-buttons');
                                    const thanks = document.getElementById('feedback-thankyou');

                                    // Disable buttons immediately
                                    const btns = buttons.getElementsByTagName('button');
                                    for (let btn of btns) btn.disabled = true;

                                    fetch('{{ route('kb.vote', $article->id) }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                        },
                                        body: JSON.stringify({ type: type })
                                    })
                                        .then(response => response.json())
                                        .then(data => {
                                            if (data.status === 'success' || data.status === 'already_voted') {
                                                buttons.style.display = 'none';
                                                thanks.style.display = 'block';
                                                // Optional: Update count visual if we wanted to keep buttons visible
                                            }
                                        })
                                        .catch(error => {
                                            console.error('Error:', error);
                                            // Re-enable on error
                                            for (let btn of btns) btn.disabled = false;
                                        });
                                }
                            </script>
                        </div>
                    </div>
                </div>

                <!-- Sidebar (Outros Artigos da Categoria) -->
                <div class="col-lg-4">
                    <div class="sidebar-kb">
                        @if($contextCategory && $contextCategory->articles->count() > 1)
                            <h6 class="font-weight-bold text-uppercase text-gray-500 mb-3 pl-3">Nesta Categoria
                                ({{ $contextCategory->name }})</h6>
                            <div class="bg-white rounded-lg shadow-sm p-3">
                                @foreach($contextCategory->articles()->where('is_public', true)->orderBy('kb_category_knowledge_base.sort_order', 'asc')->get() as $related)
                                    <a href="{{ route('kb.show', ['slug' => $related->slug ?? 'artigo-' . $related->id, 'c' => $contextCategory->slug]) }}"
                                        class="sidebar-link {{ $related->id == $article->id ? 'active' : '' }}">
                                        {{ $related->pivot->sort_order ?? 0 }} - {{ $related->title }}
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        <!-- Box de Suporte -->
                        <div class="card border-0 bg-primary text-white mt-4 shadow-sm"
                            style="background: linear-gradient(135deg, var(--primary-gradient-start), var(--primary-gradient-end));">
                            <div class="card-body text-center p-4">
                                <h5 class="font-weight-bold mb-2">Ainda precisa de ajuda?</h5>
                                <p class="small opacity-80 mb-3">Nossa equipe de suporte está pronta para te atender.</p>
                                <a href="{{ route('filament.app.pages.dashboard') }}"
                                    class="btn btn-light btn-sm font-weight-bold text-primary shadow-sm px-4 py-2 rounded-pill">Abrir
                                    Ticket</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection