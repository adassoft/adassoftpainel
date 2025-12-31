<x-filament-panels::page>
    <!-- Top Status Banner -->
    <div class="mb-6">
        @if($record->status_aprovacao === 'pendente')
            <div
                class="w-full bg-yellow-100 text-yellow-800 px-4 py-3 rounded-md flex items-center gap-2 shadow-sm border border-yellow-200">
                <svg class="h-6 w-6 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                        clip-rule="evenodd" />
                </svg>
                <span class="font-semibold text-sm">Em Análise: Suas alterações estão pendentes de aprovação visual.</span>
            </div>
        @elseif($record->status_aprovacao === 'rejeitado')
            <div
                class="w-full bg-red-100 text-red-800 px-4 py-3 rounded-md flex items-center gap-2 shadow-sm border border-red-200">
                <svg class="h-6 w-6 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        clip-rule="evenodd" />
                </svg>
                <span class="font-semibold text-sm">Alterações Rejeitadas: {{ $record->mensagem_rejeicao }}</span>
            </div>
        @elseif($record->status_aprovacao === 'aprovado')
            <div
                class="w-full bg-[#d1fae5] text-[#065f46] px-4 py-3 rounded-md flex items-center gap-2 shadow-sm border border-[#a7f3d0]">
                <svg class="h-5 w-5 bg-green-500 text-white rounded-full p-0.5" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="font-semibold text-sm">Configuração Ativa e Aprovada.</span>
            </div>
        @endif
    </div>

    <!-- Layout Principal: Lado a Lado (50% / 50%) -->
    <div class="flex flex-col lg:flex-row gap-6 w-full items-start">

        <!-- Coluna Esquerda: Form (50%) -->
        <div
            class="w-full lg:w-1/2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800">
                <h3 class="font-semibold text-blue-600 dark:text-blue-400">Editar Aparência</h3>
            </div>

            <div class="p-6">
                <form wire:submit="submit">
                    {{ $this->form }}

                    <div class="mt-8">
                        <x-filament::button type="submit" size="xl"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 shadow-lg transition-transform transform active:scale-95">
                            <span class="flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                                Salvar e Solicitar Aprovação
                            </span>
                        </x-filament::button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Coluna Direita: Preview (50%) -->
        <div class="w-full lg:w-1/2 lg:sticky lg:top-20">
            <div class="space-y-6">
                <!-- Card Container do Preview -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800">
                        <h3 class="font-semibold text-blue-600 dark:text-blue-400">Prévia em Tempo Real</h3>
                        <p class="text-xs text-gray-400 hidden sm:block float-right -mt-5">Simulação Login / Registro
                        </p>
                    </div>

                    <div class="p-6">
                        <!-- O Gradiente Grande -->
                        <div class="w-full rounded-xl shadow-inner overflow-hidden flex flex-col items-center justify-center p-8 transition-colors duration-500 relative"
                            style="background: linear-gradient(135deg, {{ $data['cor_primaria_gradient_start'] ?? '#1a2980' }}, {{ $data['cor_primaria_gradient_end'] ?? '#26d0ce' }}); min-height: 350px;">

                            <!-- Texto Branding (Sobre o gradiente) -->
                            <div class="text-center mb-8 text-white relative z-10 w-full">
                                <h1 class="text-3xl font-bold tracking-tight mb-2 break-words"
                                    style="text-shadow: 0 2px 10px rgba(0,0,0,0.2);">
                                    {{ $data['nome_sistema'] ?: 'NOME DO SISTEMA' }}
                                </h1>
                                <p class="text-base font-light opacity-90 tracking-wide">
                                    {{ $data['slogan'] ?: 'Gestão Completa' }}
                                </p>
                            </div>

                            <!-- Box de Login (Branco, centralizado) -->
                            <div
                                class="bg-white rounded-lg shadow-2xl p-6 w-72 text-center relative z-10 transform transition-transform hover:scale-[1.02]">
                                <p class="text-gray-600 text-sm mb-5 font-medium">Faça seu login</p>

                                <div class="space-y-4">
                                    <div class="h-10 bg-gray-100 rounded w-full animate-pulse"></div>
                                    <div class="h-10 bg-gray-100 rounded w-full animate-pulse"></div>

                                    <!-- Botão com cor do gradiente -->
                                    <div class="h-10 rounded-full w-32 text-white text-xs font-bold uppercase tracking-wider flex items-center justify-center shadow-lg mt-6 mx-auto transition-transform active:scale-95 cursor-default"
                                        style="background: linear-gradient(to right, {{ $data['cor_primaria_gradient_start'] ?? '#1a2980' }}, {{ $data['cor_primaria_gradient_end'] ?? '#26d0ce' }});">
                                        Entrar
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Logo (Fora do gradiente, abaixo) -->
                        <div class="mt-8 text-center">
                            <p class="text-xs text-gray-400 uppercase tracking-widest font-semibold mb-3">Logo:</p>
                            
                            <div class="flex justify-center h-20 items-center bg-gray-50 rounded-lg border border-dashed border-gray-200 p-4">
                                @php
                                    $logoUrl = null;
                                    $logoState = $data['logo_path'] ?? null;
                                    
                                    // Tratamento para Preview Live vs Arquivo Salvo
                                    if ($logoState) {
                                        if (is_string($logoState)) {
                                            $logoUrl = \Illuminate\Support\Facades\Storage::url($logoState);
                                        } elseif (is_array($logoState)) {
                                             // FileUpload pode retornar array
                                             $first = reset($logoState);
                                             if (is_string($first)) {
                                                $logoUrl = \Illuminate\Support\Facades\Storage::url($first);
                                             } elseif (is_object($first) && method_exists($first, 'temporaryUrl')) {
                                                $logoUrl = $first->temporaryUrl();
                                             }
                                        } 
                                        // Upload temporário direto (menos comum no Filament Form State array, mas possível)
                                        elseif (is_object($logoState) && method_exists($logoState, 'temporaryUrl')) {
                                            $logoUrl = $logoState->temporaryUrl();
                                        }
                                    }
                                @endphp

                                @if($logoUrl)
                                    <img src="{{ $logoUrl }}" class="max-h-full max-w-[200px] object-contain">
                                @else
                                    <div class="h-12 w-12 bg-blue-600 text-white rounded-bl-xl rounded-tr-xl flex items-center justify-center shadow-lg transform rotate-3">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Histórico (Rodapé - Largura Total) -->
    <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700">
        <h3 class="text-sm font-semibold text-blue-500 flex items-center gap-2 mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Histórico do Processo
        </h3>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700">
            @php
                $historico = $this->getHistory();
            @endphp

            @if($historico->isEmpty())
                    <div class="p-4 text-sm text-gray-500">Nenhum registro.</div>
            @else
                    @foreach($historico as $h)
                    <div class="p-4 flex items-start gap-4">
                        <div class="shrink-0 mt-1">
                            @if($h->acao == 'aprovacao')
                                <span class="flex items-center justify-center w-6 h-6 rounded-full bg-green-100 text-green-600">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                </span>
                            @elseif($h->acao == 'solicitacao')
                                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 text-blue-600">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"></path></svg>
                                    </span>
                            @else
                                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-gray-100 text-gray-600">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                                    </span>
                            @endif
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase {{ $h->acao == 'aprovacao' ? 'text-green-600' : 'text-blue-600' }}">
                                {{ $h->acao }} - {{ $h->data_registro->format('d/m/Y H:i') }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                {{ $h->mensagem }}
                            </p>
                        </div>
                    </div>
                    @endforeach
            @endif
        </div>
    </div>
</x-filament-panels::page>