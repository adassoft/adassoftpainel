@extends('layouts.app')

@section('title', 'Compra Confirmada!')

@section('content')
    <div class="container mx-auto px-4 py-16 text-center">
        <div class="max-w-xl mx-auto bg-white p-8 rounded-2xl shadow-xl">
            <div class="inline-flex items-center justify-center w-24 h-24 bg-green-100 text-green-500 rounded-full mb-6">
                <i class="fas fa-check text-5xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-4">Pagamento Confirmado!</h1>
            <p class="text-gray-600 mb-8">
                Obrigado por sua compra. Seu pedido <strong>#{{ $order->id }}</strong> foi processado com sucesso.
            </p>

            <a href="{{ $redirectUrl }}"
                class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg transition transform hover:scale-105">
                Acessar Painel / Produto
            </a>
        </div>
    </div>

    {{-- Google Analytics Purchase Event --}}
    @if(isset($order))
        <script>
            if (typeof gtag === 'function') {
                gtag('event', 'purchase', {
                    transaction_id: '{{ $order->external_reference ?? $order->id }}',
                    value: {{ $order->total ?? $order->valor }},
                    currency: 'BRL',
                    items: [
                        {
                            item_id: '{{ $order->plano_id ? "PLAN-" . $order->plano_id : "DL-" . ($order->items->first()->download_id ?? "0") }}',
                            item_name: '{{ $order->plano->nome_plano ?? ($order->items->first()->product_name ?? "Produto") }}',
                            price: {{ $order->total ?? $order->valor }},
                            quantity: 1
                        }
                    ]
                });
            }
        </script>
    @endif
@endsection