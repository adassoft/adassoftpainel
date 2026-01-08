@extends('layouts.app')

@section('title', $category->name . ' - Central de Ajuda')

@section('content')
    <!-- Header da Categoria -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 py-16 dark:from-gray-800 dark:to-gray-900">
        <div class="container mx-auto px-4 text-center">
            <div class="mb-4 flex justify-center text-white opacity-90">
                @if (str_contains($category->icon ?? '', '<svg'))
                    <div class="w-16 h-16 [&>svg]:w-full [&>svg]:h-full">
                        {!! $category->icon !!}
                    </div>
                @else
                    @php
                        try {
                            echo svg($category->icon ?? 'heroicon-o-folder', 'w-16 h-16')->toHtml();
                        } catch (\Exception $e) {
                            echo '<i class="fas fa-folder fa-4x"></i>';
                        }
                    @endphp
                @endif
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-4">{{ $category->name }}</h1>
            <p class="text-lg text-blue-100 max-w-2xl mx-auto">{{ $category->description }}</p>

            <div class="mt-8">
                <a href="{{ route('kb.index') }}"
                    class="inline-flex items-center text-white/80 hover:text-white transition-colors">
                    <x-heroicon-m-arrow-left class="w-5 h-5 mr-2" />
                    Voltar para a Central de Ajuda
                </a>
            </div>
        </div>
    </div>

    <!-- Lista de Artigos (Cards) -->
    <div class="container mx-auto px-4 py-12">
        @if ($articles->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($articles as $article)
                    <a href="{{ route('kb.show', $article->slug ?? 'artigo-' . $article->id) }}"
                        class="group block bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-all duration-300 border border-gray-100 dark:border-gray-700 overflow-hidden h-full flex flex-col">
                        <div class="p-6 flex-1">
                            <div class="flex items-start justify-between mb-4">
                                <div
                                    class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400 group-hover:bg-blue-600 group-hover:text-white transition-colors duration-300">
                                    <x-heroicon-o-document-text class="w-6 h-6" />
                                </div>
                                <span class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ $article->updated_at->format('d/m/Y') }}
                                </span>
                            </div>

                            <h3
                                class="text-xl font-bold text-gray-900 dark:text-white mb-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                {{ $article->title }}
                            </h3>

                            <p class="text-gray-600 dark:text-gray-300 text-sm line-clamp-3 mb-4">
                                {{ Str::limit(strip_tags($article->content), 120) }}
                            </p>
                        </div>
                        <div
                            class="px-6 py-4 border-t border-gray-50 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex items-center justify-between mt-auto">
                            <span class="text-sm font-medium text-blue-600 dark:text-blue-400">Ler artigo</span>
                            <x-heroicon-m-arrow-right
                                class="w-4 h-4 text-blue-600 dark:text-blue-400 transform group-hover:translate-x-1 transition-transform" />
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-12">
                {{ $articles->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <div class="bg-gray-50 dark:bg-gray-800 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                    <x-heroicon-o-document-magnifying-glass class="w-10 h-10 text-gray-400" />
                </div>
                <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-2">Nenhum artigo encontrado</h3>
                <p class="text-gray-500 dark:text-gray-400">Não há artigos públicos disponíveis nesta categoria no momento.
                </p>
                <div class="mt-6">
                    <a href="{{ route('kb.index') }}"
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none">
                        Voltar ao Início
                    </a>
                </div>
            </div>
        @endif
    </div>
@endsection