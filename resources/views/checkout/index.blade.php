@extends('layouts.app')

@section('title', 'Checkout')

@section('content')
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>

    <div class="container mx-auto px-4 py-12">
        <div class="max-w-6xl mx-auto">
            <div class="flex flex-col lg:flex-row gap-8">

                <!-- ESQUERDA: Resumo do Pedido -->
                <div class="w-full lg:w-1/2 p-6 bg-white rounded-2xl shadow-lg border h-fit">
                    <h2 class="text-xl font-bold text-gray-800 mb-6 border-b pb-4">Resumo do Pedido</h2>

                    @php
                        $product = $plan->software;
                        $imgPathRaw = $product->imagem_destaque ?: $product->imagem;
                        $displayPath = '/img/placeholder_card.svg';

                        if ($imgPathRaw) {
                            if (filter_var($imgPathRaw, FILTER_VALIDATE_URL)) {
                                $path = parse_url($imgPathRaw, PHP_URL_PATH);
                                $displayPath = '/' . ltrim($path, '/');
                            } else {
                                $displayPath = '/' . ltrim($imgPathRaw, '/');
                            }
                        }

                        $valorShow = $plan->preco_final;
                        $periodName = match ($plan->recorrencia) {
                            1 => 'Mensal',
                            6 => 'Semestral',
                            12 => 'Anual',
                            default => $plan->recorrencia . ' Meses'
                        };
                    @endphp

                    <div class="flex items-start gap-4 mb-6">
                        <img src="{{ $displayPath }}" alt="{{ $product->nome_software }}"
                            class="w-24 h-24 object-contain rounded-lg border bg-white p-2">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">{{ $product->nome_software }}</h3>
                            <span
                                class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full font-semibold uppercase tracking-wide mt-1">Plano
                                {{ $periodName }}</span>
                            <p class="text-gray-500 text-sm mt-2 leading-relaxed">
                                {{ $plan->descricao ?? 'Acesso completo ao software com suporte e atualizações.' }}
                            </p>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                        <div class="flex justify-between items-center mb-2 text-sm">
                            <span class="text-gray-600">Valor do Plano</span>
                            <span class="font-medium text-gray-900">R$ {{ number_format($valorShow, 2, ',', '.') }}</span>
                        </div>
                        <div class="border-t my-2 border-gray-200"></div>
                        <div class="flex justify-between items-center text-xl font-bold text-gray-900">
                            <span>Total</span>
                            <span>R$ {{ number_format($valorShow, 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <!-- DIREITA: Identificação ou Pagamento -->
                <div class="w-full lg:w-1/2 p-8 bg-white rounded-2xl shadow-lg border">
                    @auth
                        <!-- LOGADO: Pagamento -->
                        <div class="animate-fade-in">
                            <div
                                class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                                <i class="fas fa-check-circle mr-3 text-xl"></i>
                                <div>
                                    <p class="font-bold">Identificado como {{ auth()->user()->nome }}</p>
                                    <p class="text-sm">{{ auth()->user()->email }} ({{ auth()->user()->cnpj }})</p>
                                </div>
                            </div>

                            <h2 class="text-xl font-bold text-gray-800 mb-6">Escolha a forma de pagamento</h2>

                            <form action="{{ route('checkout.pix', $plan->id) }}" method="POST">
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
                                            <div class="text-xs font-normal opacity-90">Aprovação Imediata</div>
                                        </div>
                                    </button>

                                    <button type="button" disabled
                                        class="w-full py-4 bg-gray-100 text-gray-400 font-bold rounded-xl border border-gray-200 cursor-not-allowed flex items-center justify-center">
                                        <i class="fas fa-credit-card mr-3 text-2xl"></i>
                                        <div class="text-left">
                                            <div class="text-lg">Cartão de Crédito</div>
                                            <div class="text-xs font-normal">Em breve</div>
                                        </div>
                                    </button>
                                </div>
                            </form>
                        </div>
                    @else
                        <!-- DESLOGADO: Login / Registro -->
                        <div class="animate-fade-in" x-data="{ tab: 'register' }">
                            <h2 class="text-xl font-bold text-gray-800 mb-2">Identificação</h2>
                            <p class="text-gray-500 text-sm mb-6">Para prosseguir, entre na sua conta ou crie uma nova.</p>

                            <!-- Tabs -->
                            <div class="flex border-b mb-6 border-gray-200">
                                <button @click="tab = 'login'"
                                    :class="{ 'border-b-2 border-blue-600 text-blue-600 font-bold': tab === 'login', 'text-gray-500 hover:text-gray-700': tab !== 'login' }"
                                    class="w-1/2 py-2 text-center transition pb-3 outline-none focus:outline-none">
                                    Já sou cliente
                                </button>
                                <button @click="tab = 'register'"
                                    :class="{ 'border-b-2 border-blue-600 text-blue-600 font-bold': tab === 'register', 'text-gray-500 hover:text-gray-700': tab !== 'register' }"
                                    class="w-1/2 py-2 text-center transition pb-3 outline-none focus:outline-none">
                                    Criar conta
                                </button>
                            </div>

                            <!-- Login Form -->
                            <div x-show="tab === 'login'" class="space-y-4">
                                @if(session('error_login'))
                                    <div class="bg-red-100 text-red-700 p-3 rounded text-sm mb-4 border border-red-200">
                                        {{ session('error_login') }}
                                    </div>
                                @endif
                                <form action="{{ route('checkout.auth') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="action" value="login">
                                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">

                                    <div class="mb-4">
                                        <label class="block text-gray-700 text-sm mb-2 font-bold">E-mail</label>
                                        <input type="email" name="email" required
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition">
                                    </div>
                                    <div class="mb-6">
                                        <label class="block text-gray-700 text-sm mb-2 font-bold">Senha</label>
                                        <input type="password" name="password" required
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition">
                                    </div>
                                    <button type="submit"
                                        class="w-full py-3 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 shadow-md transition">
                                        Entrar e Continuar
                                    </button>
                                </form>
                            </div>

                            <!-- Register Form -->
                            <div x-show="tab === 'register'" class="space-y-4">
                                @if ($errors->any())
                                    <div class="bg-red-50 text-red-500 text-sm p-3 rounded border border-red-200">
                                        <ul class="list-disc pl-4">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                <form action="{{ route('checkout.auth') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="action" value="register">
                                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">

                                    <div class="mb-3">
                                        <label class="block text-gray-700 text-xs font-bold uppercase mb-1">Nome
                                            Completo</label>
                                        <input type="text" name="nome" value="{{ old('nome') }}" required
                                            class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 outline-none transition">
                                    </div>

                                    <div class="flex gap-3 mb-3">
                                        <div class="w-2/3">
                                            <label class="block text-gray-700 text-xs font-bold uppercase mb-1">CPF/CNPJ</label>
                                            <input type="text" name="cnpj" value="{{ old('cnpj') }}" required
                                                placeholder="Somente números"
                                                class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 outline-none transition">
                                        </div>
                                        <div class="w-1/3">
                                            <label class="block text-gray-700 text-xs font-bold uppercase mb-1">UF</label>
                                            <select name="uf" required
                                                class="w-full p-2 border border-gray-300 rounded bg-white focus:ring-2 focus:ring-blue-500 outline-none transition">
                                                @foreach(['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'] as $uf)
                                                    <option value="{{ $uf }}" {{ old('uf') == $uf ? 'selected' : '' }}>{{ $uf }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="block text-gray-700 text-xs font-bold uppercase mb-1">Razão Social / Nome
                                            Fantasia (Opcional)</label>
                                        <input type="text" name="razao" value="{{ old('razao') }}"
                                            placeholder="Se diferente do nome"
                                            class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 outline-none transition">
                                    </div>

                                    <div class="mb-3">
                                        <label class="block text-gray-700 text-xs font-bold uppercase mb-1">E-mail</label>
                                        <input type="email" name="email" value="{{ old('email') }}" required
                                            class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 outline-none transition">
                                    </div>

                                    <div class="mb-4">
                                        <label class="block text-gray-700 text-xs font-bold uppercase mb-1">Crie uma
                                            Senha</label>
                                        <input type="password" name="password" required
                                            class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 outline-none placeholder-gray-400 transition"
                                            placeholder="Mínimo 8 caracteres">
                                    </div>

                                    <button type="submit"
                                        class="w-full py-3 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 shadow-md transition transform hover:scale-[1.01]">
                                        Criar Conta e Pagar
                                    </button>
                                    <p class="text-xs text-center text-gray-500 mt-2">Ao criar conta você concorda com os termos
                                        de uso.</p>
                                </form>
                            </div>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </div>
@endsection