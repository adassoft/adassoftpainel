@extends('layouts.app')

@section('title', 'Acesso Restrito - ' . $download->titulo)

@section('content')
    <div class="min-h-[70vh] flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-2xl shadow-xl">
            <div class="text-center">
                <div class="mx-auto h-16 w-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                        </path>
                    </svg>
                </div>
                <h2 class="mt-2 text-3xl font-extrabold text-gray-900">
                    Download Restrito
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    {{ $download->titulo }}
                </p>
                <p class="mt-4 text-sm text-gray-500">
                    Para baixar este arquivo, preencha o formulário abaixo. Enviaremos o <strong>link de download
                        seguro</strong> para o seu e-mail imediatamente.
                </p>
            </div>

            @if(session('error'))
                <div class="bg-red-50 text-red-600 p-4 rounded-lg text-sm text-center">
                    {{ session('error') }}
                </div>
            @endif

            <form class="mt-8 space-y-6" action="{{ route('downloads.lead.store') }}" method="POST">
                @csrf
                <input type="hidden" name="download_id" value="{{ $download->id }}">
                <input type="hidden" name="version_id" value="{{ $versionId ?? '' }}">

                <div class="rounded-md shadow-sm -space-y-px">
                    <div class="mb-4">
                        <label for="empresa" class="sr-only">Empresa</label>
                        <input id="empresa" name="empresa" type="text" required
                            class="appearance-none rounded-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                            placeholder="Nome da Empresa *">
                    </div>
                    <div class="mb-4">
                        <label for="nome" class="sr-only">Nome Completo</label>
                        <input id="nome" name="nome" type="text" required
                            class="appearance-none rounded-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                            placeholder="Seu Nome *">
                    </div>
                    <div class="mb-4">
                        <label for="email" class="sr-only">E-mail Corporativo</label>
                        <input id="email" name="email" type="email" autocomplete="email" required
                            class="appearance-none rounded-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                            placeholder="E-mail Corporativo *">
                    </div>
                    <div>
                        <label for="whatsapp" class="sr-only">WhatsApp</label>
                        <input id="whatsapp" name="whatsapp" type="text"
                            class="appearance-none rounded-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                            placeholder="WhatsApp / Telefone (Opcional)">
                    </div>
                </div>

                @if(!empty($recaptchaSiteKey))
                    <div class="flex justify-center">
                        <div class="g-recaptcha" data-sitekey="{{ $recaptchaSiteKey }}"></div>
                        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                    </div>
                @endif

                <div>
                    <button type="submit"
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-blue-500 group-hover:text-blue-400" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                            </svg>
                        </span>
                        Enviar Link por E-mail
                    </button>
                </div>

                <div class="text-xs text-center text-gray-400">
                    Seus dados estão seguros. Não enviamos spam.
                </div>
            </form>
        </div>
    </div>
@endsection