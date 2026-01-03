@extends('layouts.app')

@section('title', 'Pagamento PIX')

@section('content')
    <script src="https://cdn.tailwindcss.com"></script>
    <div class="container mx-auto px-4 py-12">
        <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-lg border overflow-hidden text-center p-8">

            <div class="mb-6">
                <div
                    class="inline-flex items-center justify-center w-16 h-16 bg-green-100 text-green-600 rounded-full mb-4">
                    <i class="fas fa-qrcode text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-800">{{ $pageTitle ?? 'Pagamento via PIX' }}</h1>
                <p class="text-gray-500 mt-2">Referente a: <strong>{{ $itemName ?? 'Pedido' }}</strong></p>
                <p class="text-gray-400 text-sm">Escaneie o QR Code ou use o Copia e Cola.</p>
            </div>

            <div class="mb-8 flex justify-center">
                {{-- Verifica se a imagem já vem com prefixo ou não. Asaas geralmente manda raw base64. --}}
                <img src="data:image/jpeg;base64,{{ $pixData->encodedImage }}" alt="QR Code PIX"
                    class="w-64 h-64 border rounded-lg p-2 shadow-sm">
            </div>

            <div class="mb-8 text-left">
                <label class="block text-gray-700 text-sm font-bold mb-2">PIX Copia e Cola:</label>
                <div class="flex gap-2">
                    <input type="text" id="pixKey" readonly value="{{ $pixData->payload }}"
                        class="w-full p-3 bg-gray-50 border rounded-lg text-gray-600 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    <button onclick="copyPix()"
                        class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 font-bold transition">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>

            <div class="bg-blue-50 text-blue-800 p-4 rounded-lg text-sm mb-6">
                <i class="fas fa-info-circle mr-2"></i> Após o pagamento, a liberação ocorrerá automaticamente (geralmente
                em segundos).
            </div>

            <div class="text-center">
                <p class="text-sm text-gray-400 mb-4">ID da transação: {{ $pixData->id }}</p>
                <a href="{{ route('home') }}" class="text-blue-600 hover:underline">Voltar para a Loja</a>
            </div>

        </div>
    </div>

    <script>
        function copyPix() {
            var copyText = document.getElementById("pixKey");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(copyText.value);
            alert("Chave PIX copiada!");
        }
    </script>
@endsection