@extends('layouts.app')

@section('title', 'Link Enviado')

@section('content')
    <div class="min-h-[60vh] flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-2xl shadow-xl text-center">
            <div class="mx-auto h-20 w-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mb-6">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <h2 class="text-3xl font-extrabold text-gray-900">
                Sucesso!
            </h2>

            <p class="text-lg text-gray-600 mt-4">
                O link de download seguro foi enviado para:
            </p>
            <p class="text-blue-600 font-bold text-xl mt-2 break-all">
                {{ $email }}
            </p>

            <p class="text-sm text-gray-500 mt-8 bg-blue-50 p-4 rounded-lg">
                Verifique sua caixa de entrada (e spam). O link é válido por <strong>24 horas</strong>.
            </p>

            <div class="mt-8">
                <a href="/" class="text-blue-600 hover:text-blue-800 font-medium">
                    &larr; Voltar para o Início
                </a>
            </div>
        </div>
    </div>
@endsection