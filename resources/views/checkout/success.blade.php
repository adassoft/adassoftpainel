@extends('layouts.app')

@section('title', 'Compra Confirmada!')

@section('content')
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-lg border-0 rounded-lg">
                    <div class="card-body p-5 text-center">
                        <!-- Icon -->
                        <div class="mb-4">
                            <div class="d-inline-block rounded-circle bg-success text-white p-4">
                                <i class="fas fa-check fa-4x"></i>
                            </div>
                        </div>
                        
                        <h1 class="h2 font-weight-bold text-gray-800 mb-3">Pagamento Confirmado!</h1>
                        
                        <p class="lead text-muted mb-4">
                            Obrigado por sua compra, <strong>{{ auth()->user()->name }}</strong>!<br>
                            Seu pedido <span class="badge badge-light border text-dark text-monospace">#{{ $order->id }}</span> foi processado com sucesso.
                        </p>

                        <!-- Order Details -->
                        <div class="card bg-light border-0 mb-4 text-left">
                            <div class="card-body">
                                <h6 class="font-weight-bold text-uppercase text-secondary mb-3 pb-2 border-bottom">Resumo do Pedido</h6>
                                
                                @foreach($order->items as $item)
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-dark font-weight-bold">{{ $item->product_name ?? 'Produto' }}</span>
                                        <span class="text-dark">R$ {{ number_format($item->price, 2, ',', '.') }}</span>
                                    </div>
                                @endforeach
                                
                                @if($order->items->count() == 0)
                                     <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-dark font-weight-bold">{{ $order->descricao ?? 'Pagamento' }}</span>
                                        <span class="text-dark">R$ {{ number_format($order->total ?? $order->valor, 2, ',', '.') }}</span>
                                    </div>
                                @endif

                                <div class="border-top pt-3 mt-3 d-flex justify-content-between align-items-center">
                                    <span class="text-secondary">Total</span>
                                    <span class="text-success h4 font-weight-bold mb-0">R$ {{ number_format($order->total ?? $order->valor, 2, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Action -->
                        <div>
                            <a href="{{ $redirectUrl }}"
                               class="btn btn-primary btn-lg px-5 shadow-sm">
                                Acessar Agora
                                <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                            
                            <p class="small text-muted mt-3">
                                Você será redirecionado automaticamente em <span id="countdown">5</span> segundos...
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Auto Redirect Script --}}
    <script>
        let seconds = 5;
        const countdownEl = document.getElementById('countdown');
        const interval = setInterval(() => {
            seconds--;
            if(countdownEl) countdownEl.innerText = seconds;
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