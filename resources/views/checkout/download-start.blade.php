@extends('layouts.app')

@section('title', 'Checkout - ' . $download->titulo)

@section('content')
    <script src="https://cdn.tailwindcss.com"></script>

    <div class="container mx-auto px-4 py-12">
        <div class="max-w-4xl mx-auto">
            <div class="flex flex-col md:flex-row gap-8">

                <!-- ESQUERDA: Resumo do Produto -->
                <div class="w-full md:w-1/2 p-6 bg-white rounded-2xl shadow-lg border h-fit">
                    <h2 class="text-xl font-bold text-gray-800 mb-6 border-b pb-4">Resumo do Pedido</h2>

                    <div class="flex items-start gap-4 mb-6">
                        @php
                            $imgPathRaw = $download->imagem;
                            $displayPath = '/img/placeholder_card.svg';

                            if ($imgPathRaw) {
                                if (filter_var($imgPathRaw, FILTER_VALIDATE_URL)) {
                                    $path = parse_url($imgPathRaw, PHP_URL_PATH);
                                    $displayPath = '/' . ltrim($path, '/');
                                } else {
                                    $displayPath = '/storage/' . ltrim($imgPathRaw, '/');
                                }
                            }
                        @endphp
                        <img src="{{ $displayPath }}" alt="{{ $download->titulo }}"
                            class="w-24 h-24 object-contain rounded-lg border bg-white p-2">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">{{ $download->titulo }}</h3>
                            <span
                                class="inline-block bg-purple-100 text-purple-800 text-xs px-2 py-1 rounded-full font-semibold uppercase tracking-wide mt-1">
                                Produto Digital
                            </span>
                            <p class="text-gray-500 text-sm mt-2 leading-relaxed">
                                Versão: {{ $download->versao ?? 'Última' }}
                            </p>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                        <div class="flex justify-between items-center mb-2 text-sm">
                            <span class="text-gray-600">Valor do Produto</span>
                            <span class="font-medium text-gray-900">R$
                                {{ number_format($download->preco, 2, ',', '.') }}</span>
                        </div>
                        <div class="border-t my-2 border-gray-200"></div>
                        <div class="flex justify-between items-center text-xl font-bold text-gray-900">
                            <span>Total</span>
                            <span>R$ {{ number_format($download->preco, 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <!-- DIREITA: Pagamento -->
                <div class="w-full md:w-1/2 p-8 bg-white rounded-2xl shadow-lg border">
                    <div
                        class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                        <i class="fas fa-user-check mr-3 text-xl"></i>
                        <div>
                            <p class="font-bold">Comprando como {{ auth()->user()->nome }}</p>
                            <p class="text-sm">{{ auth()->user()->email }}</p>
                        </div>
                    </div>

                    <h2 class="text-xl font-bold text-gray-800 mb-6">Concluir Compra</h2>

                    <form action="{{ route('checkout.download.process', $download->id) }}" method="POST">
                        @csrf
                        @if(session('error'))
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                                {{ session('error') }}
                            </div>
                        @endif

                        <div class="space-y-4">
                            <button type="submit"
                                class="w-full py-4 bg-green-600 text-white font-bold rounded-xl shadow-lg hover:bg-green-700 transition flex items-center justify-center transform hover:scale-[1.02] duration-200">
                                <i class="fas fa-qrcode mr-3 text-2xl"></i>
                                <div class="text-left">
                                    <div class="text-lg">Pagar com PIX</div>
                                    <div class="text-xs font-normal opacity-90">Liberação Imediata</div>
                                </div>
                            </button>

                            <p class="text-xs text-center text-gray-500 mt-2">
                                Ao confirmar, um código QR para pagamento será gerado.
                            </p>
                        </div>
                    </form>

                    <div class="mt-6 text-center">
                        <a href="{{ route('downloads.show', $download->slug ?? $download->id) }}"
                            class="text-gray-500 hover:text-gray-700 text-sm">
                            Cancelar e Voltar
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection