@extends('layouts.app')

@section('title', $page->title)

@section('content')
    <!-- Header Simples -->
    <div class="bg-white border-b py-6">
        <div class="container mx-auto px-4">
            <p class="text-sm text-gray-500 uppercase tracking-widest font-semibold">Documento Legal</p>
        </div>
    </div>

    <div class="bg-gray-50 py-12 min-h-screen">
        <div class="container mx-auto px-4 max-w-4xl">
            <div class="bg-white rounded-2xl shadow-sm p-8 md:p-12">
                <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-8 pb-4 border-b border-gray-100">
                    {{ $page->title }}
                </h1>

                <div class="prose prose-blue max-w-none text-gray-700 leading-relaxed">
                    {!! $page->content !!}
                </div>

                <div
                    class="mt-12 pt-6 border-t border-gray-100 flex flex-col md:flex-row justify-between text-sm text-gray-500">
                    <span>Documento oficial da AdasSoft</span>
                    <span>Atualizado em: {{ $page->updated_at->format('d/m/Y') }}</span>
                </div>
            </div>
        </div>
    </div>
@endsection