@extends('layouts.app')

@section('title', 'Compra Confirmada!')

@section('content')
    <div class="container mx-auto px-4 py-16 text-center">
        <div class="max-w-2xl mx-auto bg-white p-10 rounded-3xl shadow-2xl border border-gray-100">
            <!-- Icon -->
            <div
                class="flex items-center justify-center w-24 h-24 bg-green-100 text-green-600 rounded-full mb-8 mx-auto animate-bounce">
                <i class="fas fa-check text-5xl"></i>
            </div>

            <h1 class="text-4xl font-extrabold text-gray-800 mb-4 tracking-tight">Pagamento Confirmado!</h1>

            <p class="text-lg text-gray-600 mb-8 max-w-lg mx-auto">
                Obrigado por sua compra, <strong>{{ auth()->user()->name }}</strong>!<br>
                Seu pedido <span class="font-mono bg-gray-100 px-2 py-1 rounded text-gray-700">#{{ $order->id }}</span> foi
                processado com sucesso.
            </p>

            <!-- Order Details -->
            <div class="bg-gray-50 rounded-xl p-6 mb-8 text-left border border-gray-200">
                <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4 border-b pb-2">Resumo do Pedido
                </h3>
                <div class="space-y-3">
                    @foreach($order->items as $item)
                        <div class="flex justify-between items-center">
                            <span class="text-gray-800 font-medium">{{ $item->product_name ?? 'Produto' }}</span>
                            <span class="text-gray-900 font-bold">R$ {{ number_format($item->price, 2, ',', '.') }}</span>
                        </div>
                    @endforeach
                    @if($order->items->count() == 0)
                        <div class="flex justify-between items-center">
                            <span class="text-gray-800 font-medium">{{ $order->descricao ?? 'Pagamento' }}</span>
                            <span class="text-gray-900 font-bold">R$
                                {{ number_format($order->total ?? $order->valor, 2, ',', '.') }}</span>
                        </div>
                    @endif
                    <div class="border-t pt-3 mt-3 flex justify-between items-center">
                        <span class="text-gray-600">Total</span>
                        <span class="text-green-600 text-xl font-bold">R$
                            {{ number_format($order->total ?? $order->valor, 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Action -->
            <div class="space-y-4">
                <a href="{{ $redirectUrl }}"
                    class="inline-block bg-blue-600 hover:bg-blue-700 text-white text-lg font-bold py-4 px-10 rounded-xl shadow-lg hover:shadow-xl transition transform hover:-translate-y-1 w-full sm:w-auto">
                    Acessar Agora
                    <i class="fas fa-arrow-right ml-2"></i>
                </a>

                <p class="text-sm text-gray-400 mt-4">
                    Você será redirecionado automaticamente em <span id="countdown">5</span> segundos...
                </p>
            </div>
        </div>
    </div>

    {{-- Auto Redirect Script --}}
    <script>
        let seconds = 5;
        const countdownEl = document.getElementById('countdown');
        const interval = setInterval(() => {
            seconds--;
            if (countdownEl) countdownEl.innerText = seconds;
            if (seconds <= 0) {
                clearInterval(interval);
                window.location.href = "{{ $redirectUrl }}";
            }
        }, 1000);
    </script>

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
                            item_name: '{{ $order->plano->nome_plano ?? ($order->items->first()->product_name ?? "Produto/Serviço") }}',
                            price: {{ $order->total ?? $order->valor }},
                            quantity: 1
                        }
                    ]
                });
            }
        </script>
    @endif
@endsection